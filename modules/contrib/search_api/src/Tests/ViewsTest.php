<?php

namespace Drupal\search_api\Tests;

use Drupal\Component\Utility\Html;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility;

/**
 * Tests the Views integration of the Search API.
 *
 * @group search_api
 */
class ViewsTest extends WebTestBase {

  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('search_api_test_views', 'views_ui');

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->setUpExampleStructure();
    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->insertExampleContent();
    $this->indexItems($this->indexId);
  }

  /**
   * Tests a view with exposed filters.
   */
  public function testView() {
    $this->checkResults(array(), array_keys($this->entities), 'Unfiltered search');

    $this->checkResults(
      array('search_api_fulltext' => 'foobar'),
      array(3),
      'Search for a single word'
    );
    $this->checkResults(
      array('search_api_fulltext' => 'foo test'),
      array(1, 2, 4),
      'Search for multiple words'
    );
    $query = array(
      'search_api_fulltext' => 'foo test',
      'search_api_fulltext_op' => 'or',
    );
    $this->checkResults($query, array(1, 2, 3, 4, 5), 'OR search for multiple words');
    $query = array(
      'search_api_fulltext' => 'foobar',
      'search_api_fulltext_op' => 'not',
    );
    $this->checkResults($query, array(1, 2, 4, 5), 'Negated search');
    $query = array(
      'search_api_fulltext' => 'foo test',
      'search_api_fulltext_op' => 'not',
    );
    $this->checkResults($query, array(), 'Negated search for multiple words');
    $query = array(
      'search_api_fulltext' => 'fo',
    );
    $label = 'Search for short word';
    $this->checkResults($query, array(), $label);
    $this->assertText('You must include at least one positive keyword with 3 characters or more', "$label displayed the correct warning.");
    $query = array(
      'search_api_fulltext' => 'foo to test',
    );
    $label = 'Fulltext search including short word';
    $this->checkResults($query, array(1, 2, 4), $label);
    $this->assertNoText('You must include at least one positive keyword with 3 characters or more', "$label didn't display a warning.");

    $this->checkResults(array('id[value]' => 2), array(2), 'Search with ID filter');
    // @todo Enable "between" again. See #2624870.
//    $query = array(
//      'id[min]' => 2,
//      'id[max]' => 4,
//      'id_op' => 'between',
//    );
//    $this->checkResults($query, array(2, 3, 4), 'Search with ID "in between" filter');
    $query = array(
      'id[value]' => 2,
      'id_op' => '>',
    );
    $this->checkResults($query, array(3, 4, 5), 'Search with ID "greater than" filter');
    $query = array(
      'id[value]' => 2,
      'id_op' => '!=',
    );
    $this->checkResults($query, array(1, 3, 4, 5), 'Search with ID "not equal" filter');
    $query = array(
      'id_op' => 'empty',
    );
    $this->checkResults($query, array(), 'Search with ID "empty" filter');
    $query = array(
      'id_op' => 'not empty',
    );
    $this->checkResults($query, array(1, 2, 3, 4, 5), 'Search with ID "not empty" filter');

    $this->checkResults(array('keywords[value]' => 'apple'), array(2, 4), 'Search with Keywords filter');
    // @todo Enable "between" again. See #2624870.
//    $query = array(
//      'keywords[min]' => 'aardvark',
//      'keywords[max]' => 'calypso',
//      'keywords_op' => 'between',
//    );
//    $this->checkResults($query, array(2, 4, 5), 'Search with Keywords "in between" filter');
    $query = array(
      'keywords[value]' => 'radish',
      'keywords_op' => '>=',
    );
    $this->checkResults($query, array(1, 4, 5), 'Search with Keywords "greater than or equal" filter');
    $query = array(
      'keywords[value]' => 'orange',
      'keywords_op' => '!=',
    );
    $this->checkResults($query, array(3, 4), 'Search with Keywords "not equal" filter');
    $query = array(
      'keywords_op' => 'empty',
    );
    $this->checkResults($query, array(3), 'Search with Keywords "empty" filter');
    $query = array(
      'keywords_op' => 'not empty',
    );
    $this->checkResults($query, array(1, 2, 4, 5), 'Search with Keywords "not empty" filter');

    $query = array(
      'search_api_fulltext' => 'foo to test',
      'id[value]' => 2,
      'id_op' => '>',
      'keywords_op' => 'not empty',
    );
    $this->checkResults($query, array(4), 'Search with multiple filters');
  }

  /**
   * Checks the Views results for a certain set of parameters.
   *
   * @param array $query
   *   The GET parameters to set for the view.
   * @param int[]|null $expected_results
   *   (optional) The IDs of the expected results; or NULL to skip checking the
   *   results.
   * @param string $label
   *   (optional) A label for this search, to include in assert messages.
   */
  protected function checkResults(array $query, array $expected_results = NULL, $label = 'Search') {
    $this->drupalGet('search-api-test', array('query' => $query));

    if (isset($expected_results)) {
      $count = count($expected_results);
      $count_assert_message = "$label returned correct number of results.";
      if ($count) {
        $this->assertText("Displaying $count search results", $count_assert_message);
      }
      else {
        $this->assertNoText('search results', $count_assert_message);
      }

      $expected_results = array_combine($expected_results, $expected_results);
      $actual_results = array();
      foreach ($this->entities as $id => $entity) {
        $entity_label = Html::escape($entity->label());
        if (strpos($this->getRawContent(), ">$entity_label<") !== FALSE) {
          $actual_results[$id] = $id;
        }
      }
      $this->assertEqual($expected_results, $actual_results, "$label returned correct results.");
    }
  }

  /**
   * Test Views admin UI and field handlers.
   */
  public function testViewsAdmin() {
    $admin_user = $this->drupalCreateUser(array(
      'administer search_api',
      'access administration pages',
      'administer views',
    ));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/search_api_test_view');
    $this->assertResponse(200);

    // Switch to "Fields" row style.
    $this->clickLink($this->t('Rendered entity'));
    $this->assertResponse(200);
    $edit = array(
      'row[type]' => 'fields',
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Apply'));
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, array(), $this->t('Apply'));
    $this->assertResponse(200);

    // Add the "User ID" relationship.
    $this->clickLink($this->t('Add relationships'));
    $edit = array(
      'name[search_api_datasource_database_search_index_entity_entity_test.user_id]' => 'search_api_datasource_database_search_index_entity_entity_test.user_id',
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Add and configure relationships'));
    $this->drupalPostForm(NULL, array(), $this->t('Apply'));

    // Add new fields. First check that the listing seems correct.
    $this->clickLink($this->t('Add fields'));
    $this->assertResponse(200);
    $this->assertText($this->t('Test entity datasource'));
    $this->assertText($this->t('Authored on'));
    $this->assertText($this->t('Body (indexed field)'));
    $this->assertText($this->t('Index Test index'));
    $this->assertText($this->t('Entity ID'));
    $this->assertText($this->t('Excerpt'));
    $this->assertText($this->t('The search result excerpted to show found search terms'));
    $this->assertText($this->t('Relevance'));
    $this->assertText($this->t('The relevance of this search result with respect to the query'));
    $this->assertText($this->t('Language code'));
    $this->assertText($this->t('The user language code.'));
    $this->assertText($this->t('(No description available)'));
    $this->assertNoText($this->t('Error: missing help'));

    // Then add some fields.
    $fields = array(
      'views.counter',
      'search_api_datasource_database_search_index_entity_entity_test.id',
      'search_api_index_database_search_index.search_api_datasource',
      'search_api_datasource_database_search_index_entity_entity_test.body',
      'search_api_index_database_search_index.category',
      'search_api_index_database_search_index.keywords',
      'search_api_datasource_database_search_index_entity_entity_test.user_id',
    );
    $edit = array();
    foreach ($fields as $field) {
      $edit["name[$field]"] = $field;
    }
    $this->drupalPostForm(NULL, $edit, $this->t('Add and configure fields'));
    $this->assertResponse(200);

    for ($i = 0; $i < count($fields); ++$i) {
      $this->submitFieldsForm();
    }

    $this->clickLink($this->t('Add filter criteria'));
    $edit = array(
      'name[search_api_index_database_search_index.name]' => 'search_api_index_database_search_index.name',
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Add and configure filter criteria'));
    $this->submitPluginForm(array());

    // Save the view.
    $this->drupalPostForm(NULL, array(), $this->t('Save'));
    $this->assertResponse(200);

    // Check the results.
    $this->drupalGet('search-api-test');
    $this->assertResponse(200);

    foreach ($this->entities as $id => $entity) {
      $fields = array(
        'search_api_datasource',
        'id',
        'body',
        'category',
        'keywords',
        // @todo This currently doesn't work correctly in the test environment.
//        'user_id',
      );
      foreach ($fields as $field) {
        if ($field != 'search_api_datasource') {
          $data = Utility::extractFieldValues($entity->get($field));
          if (!$data) {
            $data = array('[EMPTY]');
          }
        }
        else {
          $data = array('entity:entity_test');
        }
        $prefix = "#$id [$field] ";
        $text = $prefix . implode("|$prefix", $data);
        $this->assertText($text, "Correct value displayed for field $field on entity #$id (\"$text\")");
      }
    }
  }

  /**
   * Submits the field handler config form currently displayed.
   */
  protected function submitFieldsForm() {
    $url_parts = explode('/', $this->getUrl());
    $field = array_pop($url_parts);

    $edit['options[fallback_options][multi_separator]'] = '|';
    $edit['options[alter][alter_text]'] = TRUE;
    $edit['options[alter][text]'] = "#{{counter}} [$field] {{ $field }}";
    $edit['options[empty]'] = "#{{counter}} [$field] [EMPTY]";

    switch ($field) {
      case 'counter':
        $edit = array(
          'options[exclude]' => TRUE,
        );
        break;

      case 'id':
        $edit['options[field_rendering]'] = FALSE;
        break;

      case 'search_api_datasource':
        unset($edit['options[fallback_options][multi_separator]']);
        break;

      case 'body':
        break;

      case 'category':
        break;

      case 'keywords':
        $edit['options[field_rendering]'] = FALSE;
        break;

      case 'user_id':
        $edit['options[field_rendering]'] = FALSE;
        $edit['options[fallback_options][display_methods][user][display_method]'] = 'id';
        break;
    }

    $this->submitPluginForm($edit);
  }

  /**
   * Submits a Views plugin's configuration form.
   *
   * @param array $edit
   *   The values to set in the form.
   */
  protected function submitPluginForm(array $edit) {
    $button_label = $this->t('Apply');
    $buttons = $this->xpath('//input[starts-with(@value, :label)]', array(':label' => $button_label));
    if ($buttons) {
      $button_label = $buttons[0]['value'];
    }

    $this->drupalPostForm(NULL, $edit, $button_label);
    $this->assertResponse(200);
  }

}
