<?php

namespace Drupal\facets\Tests;

use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class IntegrationTest extends WebTestBase {

  /**
   * The block entities used by this test.
   *
   * @var \Drupal\block\BlockInterface[]
   */
  protected $blocks;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = NULL;
  }

  /**
   * Tests Facets' permissions.
   */
  public function testOverviewPermissions() {
    $facet_overview = '/admin/config/search/facets';

    // Login with a user that is not authorized to administer facets and test
    // that we're correctly getting a 403 HTTP response code.
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet($facet_overview);
    $this->assertResponse(403);
    $this->assertText('You are not authorized to access this page');

    // Login with a user that has the correct permissions and test for the
    // correct HTTP response code.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($facet_overview);
    $this->assertResponse(200);
  }

  /**
   * Tests various operations via the Facets' admin UI.
   */
  public function testFramework() {
    $facet_name = "Test Facet name";
    $facet_id = 'test_facet_name';

    // Check if the overview is empty.
    $this->checkEmptyOverview();

    // Add a new facet and edit it. Check adding a duplicate.
    $this->addFacet($facet_name);
    $this->editFacet($facet_name);
    $this->addFacetDuplicate($facet_name);

    // By default, the view should show all entities.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 5 search results', 'The search view displays the correct number of results.');

    // Create and place a block for "Test Facet name" facet.
    $this->createFacetBlock($facet_id);

    // Verify that the facet results are correct.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('item');
    $this->assertText('article');

    // Verify that facet blocks appear as expected.
    $this->assertFacetBlocksAppear();

    // Verify that the facet only shows when the facet source is visible.
    $this->setOptionShowOnlyWhenFacetSourceVisible($facet_name);
    $this->goToDeleteFacetPage($facet_name);
    $this->assertNoText('item');
    $this->assertNoText('article');

    // Do not show the block on empty behaviors.
    $this->clearIndex();
    $this->drupalGet('search-api-test-fulltext');

    // Verify that no facet blocks appear. Empty behavior "None" is selected by
    // default.
    $this->assertNoFacetBlocksAppear();

    // Verify that the "empty_text" appears as expected.
    $this->setEmptyBehaviorFacetText($facet_name);
    $this->drupalGet('search-api-test-fulltext');
    $this->assertRaw('block-test-facet-name');
    $this->assertRaw('No results found for this block!');

    // Delete the block.
    $this->deleteBlock($facet_id);

    // Delete the facet and make sure the overview is empty again.
    $this->deleteUnusedFacet($facet_name);
    $this->checkEmptyOverview();
  }

  /**
   * Tests that a block view also works.
   */
  public function testBlockView() {
    $facet_name = "Block view facet";
    $facet_id = 'bvf';

    // Add a new facet.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $facet_id,
      'status' => 1,
      'url_alias' => $facet_id,
      'name' => $facet_name,
      'weight' => 2,
      'facet_source_id' => 'search_api_views:search_api_test_view:block_1',
      'facet_source_configs[search_api_views:search_api_test_view:block_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:block_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));

    $facet = Facet::load($facet_id);
    $this->assertEqual($facet_name, $facet->label());
    $this->assertEqual(FALSE, $facet->getOnlyVisibleWhenFacetSourceIsVisible());

    // Place the views block in the footer of all pages.
    $block_settings = [
      'region' => 'footer',
      'id' => 'view_block',
    ];
    $this->drupalPlaceBlock('views_block:search_api_test_view-block_1', $block_settings);

    // By default, the view should show all entities.
    $this->drupalGet('<front>');
    $this->assertText('Displaying 5 search results', 'The search view displays the correct number of results.');
    $this->assertText('Fulltext test index', 'The search view displays the correct number of results.');

    // Create and place a block for the test facet.
    $this->createFacetBlock($facet_id);

    // Verify that the facet results are correct displayed.
    $this->drupalGet('<front>');
    $this->assertText('item');
    $this->assertText('article');

    // Click the item link, and test that filtering of results actually works.
    $this->clickLink('item');
    $this->assertText('Displaying 3 search results', 'The search view displays the correct number of results.');
  }

  /**
   * Tests renaming of a facet.
   *
   * @see https://www.drupal.org/node/2629504
   */
  public function testRenameFacet() {

    // Set names.
    $facet_id = 'ab_facet';
    $new_facet_id = 'facet__ab';
    $facet_name = 'ab>Facet';

    // Make sure we're logged in with a user that has sufficient permissions.
    $this->drupalLogin($this->adminUser);

    // Add a new facet.
    $this->addFacet($facet_name);

    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_edit_page);
    $this->assertResponse(200);
    $this->assertRaw($this->t('Edit facet @facet', ['@facet' => $facet_name]));

    // Change the machine name to a new name and check that the redirected page
    // is the correct url.
    $form = ['id' => $new_facet_id];
    $this->drupalPostForm($facet_edit_page, $form, $this->t('Save'));

    $expected_url = '/admin/config/search/facets/' . $new_facet_id . '/edit';
    $this->assertUrl($expected_url);
  }

  /**
   * Tests that an url alias works correctly.
   */
  public function testUrlAlias() {
    $facet_id = 'ab_facet';
    $facet_name = 'ab>Facet';

    // Make sure we're logged in with a user that has sufficient permissions.
    $this->drupalLogin($this->adminUser);

    $facet_add_page = '/admin/config/search/facets/add-facet';
    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';

    $this->drupalGet($facet_add_page);
    $this->assertResponse(200);

    $form_values = [
      'name' => $facet_name,
      'id' => $facet_id,
      'status' => 1,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
      'weight' => 4,
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('The name of the facet for usage in URLs field is required.'));

    $form_values['url_alias'] = 'test';
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertRaw(t('Facet %name has been created.', ['%name' => $facet_name]));

    $this->createFacetBlock($facet_id);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'test:item']]);
    $this->assertUrl($url);

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['url_alias' => 'llama'], $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'llama:item']]);
    $this->assertUrl($url);
  }

  /**
   * Tests facet dependencies.
   */
  public function testFacetDependencies() {
    $facet_name = "DependableFacet";
    $facet_id = 'dependablefacet';
    $this->addFacet($facet_name);

    $depending_facet_name = "DependingFacet";
    $depending_facet_id = "dependingfacet";
    $this->addFacet($depending_facet_name, 'keywords');

    // Create both facets as blocks and add them on the page.
    $this->createFacetBlock($facet_id);
    $this->createFacetBlock($depending_facet_id);

    // Go the the view and test that both facets are shown. Item and article
    // come from the DependableFacet, orange and grape come from DependingFacet.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('grape');
    $this->assertLink('orange');
    $this->assertLink('item');
    $this->assertLink('article');
    $this->assertFacetBlocksAppear();

    // Change the visiblity settings of the DependingFacet.
    $this->drupalGet('admin/structure/block/manage/dependingfacet');
    $edit = [
      'visibility[other_facet][facets]' => 'facet_block:dependablefacet',
      'visibility[other_facet][facet_value]' => 'item',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save block'));
    $this->assertText('The block configuration has been saved.');

    // Go to the view and test that only the types are shown.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertNoLink('grape');
    $this->assertNoLink('orange');
    $this->assertLink('item');
    $this->assertLink('article');

    // Click on the item, and test that this shows the keywords.
    $this->clickLink('item');
    $this->assertLink('grape');
    $this->assertLink('orange');

    // Go back to the view, click on article and test that the keywords are
    // hidden.
    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('article');
    $this->assertNoLink('grape');
    $this->assertNoLink('orange');

    // Change the visibility settings to negate the previous settings.
    $this->drupalGet('admin/structure/block/manage/dependingfacet');
    $edit = [
      'visibility[other_facet][facets]' => 'facet_block:dependablefacet',
      'visibility[other_facet][facet_value]' => 'item',
      'visibility[other_facet][negate]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save block'));

    // Go the the view and test only the type facet is shown.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');
    $this->assertLink('grape');
    $this->assertLink('orange');

    // Click on the article, and test that this shows the keywords.
    $this->clickLink('article');
    $this->assertLink('grape');
    $this->assertLink('orange');

    // Go back to the view, click on item and test that the keywords are
    // hidden.
    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('item');
    $this->assertNoLink('grape');
    $this->assertNoLink('orange');
  }

  /**
   * Tests the facet's and/or functionality.
   */
  public function testAndOrFacet() {
    $facet_name = 'test & facet';
    $facet_id = 'test_facet';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id . '/display';

    $this->drupalLogin($this->adminUser);
    $this->addFacet($facet_name);
    $this->createFacetBlock('test_facet');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[query_operator]' => 'AND'], $this->t('Save'));

    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');
    $this->assertLink('(-) item');
    $this->assertNoLink('article');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[query_operator]' => 'OR'], $this->t('Save'));
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');
    $this->assertLink('(-) item');
    $this->assertLink('article');
  }

  /**
   * Tests that we disallow unwanted values.
   */
  public function testUnwantedValues() {
    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertResponse(200);

    // Configure the facet source by selecting one of the Search API views.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));

    // Fill in all fields and make sure the 'field is required' message is no
    // longer shown.
    $facet_source_form = [
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, $facet_source_form, $this->t('Save'));

    $form_values = [
      'name' => 'name 1',
      'id' => 'name 1',
      'status' => 1,
      'url_alias' => 'name',
    ];
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $form_values = [
      'name' => 'name 1',
      'id' => 'name:&1',
      'status' => 1,
      'url_alias' => 'name',
    ];
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $form_values = [
      'name' => 'name 1',
      'id' => 'name_1',
      'status' => 1,
      'url_alias' => 'name:1',
    ];
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    $form_values = [
      'name' => 'name 1',
      'id' => 'name_1',
      'status' => 1,
      'url_alias' => 'name_1',
    ];
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertNoText($this->t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));
  }

  /**
   * Tests the facet's exclude functionality.
   */
  public function testExcludeFacet() {
    $facet_name = 'test & facet';
    $facet_id = 'test_facet';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id . '/display';

    $this->addFacet($facet_name);
    $this->createFacetBlock($facet_id);

    $this->drupalGet($facet_edit_page);
    $this->assertNoFieldChecked('edit-facet-settings-exclude');
    $this->drupalPostForm(NULL, ['facet_settings[exclude]' => TRUE], $this->t('Save'));
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-facet-settings-exclude');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('foo bar baz');
    $this->assertText('foo baz');
    $this->assertLink('item');

    $this->clickLink('item');
    $this->assertLink('(-) item');
    $this->assertText('foo baz');
    $this->assertText('bar baz');
    $this->assertNoText('foo bar baz');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['facet_settings[exclude]' => FALSE], $this->t('Save'));
    $this->assertResponse(200);
    $this->assertNoFieldChecked('edit-facet-settings-exclude');

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('foo bar baz');
    $this->assertText('foo baz');
    $this->assertLink('item');

    $this->clickLink('item');
    $this->assertLink('(-) item');
    $this->assertText('foo bar baz');
    $this->assertText('foo test');
    $this->assertText('bar');
    $this->assertNoText('foo baz');
  }

  /**
   * Tests allow only one active item.
   */
  public function testAllowOneActiveItem() {
    $facet_name = 'Spotted wood owl';
    $facet_id = 'spotted_wood_owl';
    $facet_edit_page = 'admin/config/search/facets/' . $facet_id;

    $this->addFacet($facet_name, 'keywords');
    $this->createFacetBlock($facet_id);

    $this->drupalGet($facet_edit_page . '/display');
    $edit = ['facet_settings[show_only_one_result]' => TRUE];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 5 search results');
    $this->assertLink('grape');
    $this->assertLink('orange');

    $this->clickLink('grape');
    $this->assertText('Displaying 3 search results');
    $this->assertLink('(-) grape');
    $this->assertLink('orange');

    $this->clickLink('orange');
    $this->assertText('Displaying 3 search results');
    $this->assertLink('grape');
    $this->assertLink('(-) orange');
  }

  /**
   * Tests facet weights.
   */
  public function testWeight() {
    $facet_name = "Forest owlet";
    $id = "forest_owlet";
    $this->addFacet($facet_name);

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::load($id);
    $facet->setWeight(10);
    $this->assertEqual(10, $facet->getWeight());
  }

  /**
   * Deletes a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function deleteBlock($id) {
    $this->drupalGet('admin/structure/block/manage/' . $this->blocks[$id]->id(), array('query' => array('destination' => 'admin')));
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The block %name has been deleted.', array('%name' => $this->blocks[$id]->label())));
  }

  /**
   * Asserts that a facet block does not appear.
   */
  protected function assertNoFacetBlocksAppear() {
    foreach ($this->blocks as $block) {
      $this->assertNoBlockAppears($block);
    }
  }

  /**
   * Asserts that a facet block appears.
   */
  protected function assertFacetBlocksAppear() {
    foreach ($this->blocks as $block) {
      $this->assertBlockAppears($block);
    }
  }

  /**
   * Creates a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function createFacetBlock($id) {
    $block = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->blocks[$id] = $this->drupalPlaceBlock($block['plugin_id'], $block['settings']);
  }

  /**
   * Configures empty behavior option to show a text on empty results.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setEmptyBehaviorFacetText($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/display';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_display_page);
    $this->assertResponse(200);

    // Configure the text for empty results behavior.
    $edit = [
      'facet_settings[empty_behavior]' => 'text',
      'facet_settings[empty_behavior_container][empty_behavior_text][value]' => 'No results found for this block!',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

  }

  /**
   * Configures a facet to only be visible when accessing to the facet source.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setOptionShowOnlyWhenFacetSourceVisible($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/display';
    $this->drupalGet($facet_display_page);
    $this->assertResponse(200);

    $edit = [
      'facet_settings[only_visible_when_facet_source_is_visible]' => TRUE,
      'widget' => 'links',
      'widget_configs[show_numbers]' => '0',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
  }

  /**
   * Tests that the facet overview is empty.
   */
  protected function checkEmptyOverview() {
    $facet_overview = '/admin/config/search/facets';
    $this->drupalGet($facet_overview);
    $this->assertResponse(200);

    // The list overview has Field: field_name as description. This tests on the
    // absence of that.
    $this->assertNoText('Field:');

    // Check that the expected facet sources are shown.
    $this->assertText('search_api_views:search_api_test_view:block_1');
    $this->assertText('search_api_views:search_api_test_view:page_1');
  }

  /**
   * Tests adding a facet trough the interface.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function addFacet($facet_name, $facet_type = 'type') {
    $facet_id = $this->convertNameToMachineName($facet_name);

    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertResponse(200);

    $form_values = [
      'name' => '',
      'id' => $facet_id,
      'status' => 1,
      'url_alias' => $facet_id,
    ];

    // Try filling out the form, but without having filled in a name for the
    // facet to test for form errors.
    $this->drupalPostForm($facet_add_page, $form_values, $this->t('Save'));
    $this->assertText($this->t('Facet name field is required.'));
    $this->assertText($this->t('Facet source field is required.'));
    $this->assertText($this->t('The weight of the facet field is required.'));

    // Make sure that when filling out the name, the form error disappears.
    $form_values['name'] = $facet_name;
    $form_values['weight'] = 15;
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertNoText($this->t('Facet name field is required.'));

    // Configure the facet source by selecting one of the Search API views.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));

    // The facet field is still required.
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('Facet field field is required.'));

    // Fill in all fields and make sure the 'field is required' message is no
    // longer shown.
    $facet_source_form = [
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => $facet_type,
    ];
    $this->drupalPostForm(NULL, $form_values + $facet_source_form, $this->t('Save'));
    $this->assertNoText('field is required.');

    // Make sure that the redirection to the display page is correct.
    $this->assertRaw(t('Facet %name has been created.', ['%name' => $facet_name]));
    $this->assertUrl('admin/config/search/facets/' . $facet_id . '/display');

    $this->drupalGet('admin/config/search/facets');
  }

  /**
   * Tests creating a facet with an existing machine name.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function addFacetDuplicate($facet_name, $facet_type = 'type') {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);

    $form_values = [
      'name' => $facet_name,
      'id' => $facet_id,
      'url_alias' => $facet_id,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'weight' => 7,
    ];

    $facet_source_configs['facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]'] = $facet_type;

    // Try to submit a facet with a duplicate machine name after form rebuilding
    // via facet source submit.
    $this->drupalPostForm(NULL, $form_values, $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values + $facet_source_configs, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name is already in use. It must be unique.'));

    // Try to submit a facet with a duplicate machine name after form rebuilding
    // via facet source submit using AJAX.
    $this->drupalPostAjaxForm(NULL, $form_values, array('facet_source_configure' => t('Configure facet source')));
    $this->drupalPostForm(NULL, $form_values + $facet_source_configs, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name is already in use. It must be unique.'));
  }

  /**
   * Tests editing of a facet through the UI.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function editFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_edit_page);
    $this->assertResponse(200);
    $this->assertRaw($this->t('Edit facet @facet', ['@facet' => $facet_name]));

    // Change the facet name to add in "-2" to test editing of a facet works.
    $form_values = ['name' => $facet_name . ' - 2'];
    $this->drupalPostForm($facet_edit_page, $form_values, $this->t('Save'));

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertRaw(t('Facet %name has been updated.', ['%name' => $facet_name . ' - 2']));

    // Make sure the "-2" suffix is still on the facet when editing a facet.
    $this->drupalGet($facet_edit_page);
    $this->assertRaw($this->t('Edit facet @facet', ['@facet' => $facet_name . ' - 2']));

    // Edit the form and change the facet's name back to the initial name.
    $form_values = ['name' => $facet_name];
    $this->drupalPostForm($facet_edit_page, $form_values, $this->t('Save'));

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertRaw(t('Facet %name has been updated.', ['%name' => $facet_name]));
  }

  /**
   * Deletes a facet through the UI that still has usages.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUsedFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertRaw($this->t("The facet is currently used in a block and thus can't be removed. Remove the block first."));
  }

  /**
   * Deletes a facet through the UI.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUnusedFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);
    // @TODO Missing this text on local test. Not sure why.
    // $this->assertText($this->t('Are you sure you want to delete the facet'));
    // Actually submit the confirmation form.
    $this->drupalPostForm(NULL, [], $this->t('Delete'));

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertRaw($this->t('The facet %facet has been deleted.', ['%facet' => $facet_name]));

    // Refresh the page because on the previous page the $facet_name is still
    // visible (in the message).
    $facet_overview = '/admin/config/search/facets';
    $this->drupalGet($facet_overview);
    $this->assertResponse(200);
    $this->assertNoText($facet_name);
  }

  /**
   * Add fields to Search API index.
   */
  protected function addFieldsToIndex() {
    $edit = [
      'fields[entity:node/nid][indexed]' => 1,
      'fields[entity:node/title][indexed]' => 1,
      'fields[entity:node/title][type]' => 'text',
      'fields[entity:node/title][boost]' => '21.0',
      'fields[entity:node/body][indexed]' => 1,
      'fields[entity:node/uid][indexed]' => 1,
      'fields[entity:node/uid][type]' => 'search_api_test_data_type',
    ];

    $this->drupalPostForm('admin/config/search/search-api/index/webtest_index/fields', $edit, $this->t('Save changes'));
    $this->assertText($this->t('The changes were successfully saved.'));
  }

  /**
   * Convert facet name to machine name.
   *
   * @param string $facet_name
   *   The name of the facet.
   *
   * @return string
   *   The facet name changed to a machine name.
   */
  protected function convertNameToMachineName($facet_name) {
    return preg_replace('@[^a-zA-Z0-9_]+@', '_', strtolower($facet_name));
  }

  /**
   * Go to the Delete Facet Page using the facet name.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function goToDeleteFacetPage($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);
  }

}
