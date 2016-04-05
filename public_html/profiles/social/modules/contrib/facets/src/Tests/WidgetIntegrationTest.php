<?php

namespace Drupal\facets\Tests;

use Drupal\Core\Url;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class WidgetIntegrationTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'node',
    'search_api',
    'search_api_test_backend',
    'facets',
    'block',
    'facets_search_api_dependency',
    'facets_query_processor',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');
  }

  /**
   * Tests various url integration things.
   */
  public function testCheckboxWidget() {
    $id = 't';
    $name = 'Facet & checkbox~';
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 13,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'checkbox'], $this->t('Save'));

    $block_values = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->drupalPlaceBlock($block_values['plugin_id'], $block_values['settings']);

    $this->drupalGet('search-api-test-fulltext');
    $this->drupalPostForm(NULL, array('type[item]' => 'item'), $this->t('submit'));
    $this->assertFieldChecked('edit-type-item');
  }

  /**
   * Tests multiple checkbox widgets.
   */
  public function testMultipleCheckboxWidget() {
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $id = 'type';
    $name = 'Northern hawk-owl | type';
    $id_2 = 'keywords';
    $name_2 = 'Papuan hawk-owl | keywords';

    // Add a new facet.
    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 12,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'checkbox'], $this->t('Save'));

    // Add a new facet.
    $form_values = [
      'id' => $id_2,
      'status' => 1,
      'url_alias' => $id_2,
      'name' => $name_2,
      'weight' => 8,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'keywords',
    ];
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'checkbox'], $this->t('Save'));

    // Place facets as blocks.
    $block_values = ['region' => 'footer', 'id' => str_replace('_', '-', $id)];
    $this->drupalPlaceBlock('facet_block:' . $id, $block_values);
    $block_values = ['region' => 'footer', 'id' => str_replace('_', '-', $id_2)];
    $this->drupalPlaceBlock('facet_block:' . $id_2, $block_values);

    // Go to the test view and test that both facets are shown on the page.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertText($name);
    $this->assertText($name_2);
    $this->assertText('item');
    $this->assertText('apple');

    // Submit the facet form and check that the form is submitted and the
    // checkbox is now checked.
    $edit = array('type[item]' => 'item');
    $this->drupalPostForm(NULL, $edit, $this->t('submit'));
    $this->assertText($name);
    $this->assertText($name_2);
    $this->assertText('item');
    $this->assertText('apple');
    $this->assertFieldChecked('edit-type-item');

    // Submit the second facet form and check that the form is submitted and the
    // checkbox is now checked.
    $edit = array('keywords[apple]' => 'apple');
    $this->drupalPostForm(NULL, $edit, $this->t('submit'));
    $this->assertText($name);
    $this->assertText($name_2);
    $this->assertText('item');
    $this->assertText('apple');
    $this->assertFieldChecked('edit-type-item');
    $this->assertFieldChecked('edit-keywords-apple');
  }

  /**
   * Tests links widget's basic functionality.
   */
  public function testLinksWidget() {
    $id = 'links_widget';
    $name = '>.Facet &* Links';
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 11,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'links'], $this->t('Save'));

    $block_values = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->drupalPlaceBlock($block_values['plugin_id'], $block_values['settings']);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');
    $this->assertLink('(-) item');
  }

  /**
   * Tests select widget's basic functionality.
   */
  public function testSelectWidget() {
    $id = 'select_widget';
    $name = 'Select';
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 10,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'select'], $this->t('Save'));

    $block_values = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->drupalPlaceBlock($block_values['plugin_id'], $block_values['settings']);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertField('edit-type', 'Dropdown is visible.');
    $this->assertText('Displaying 5 search results');

    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'select_widget:item']]);
    $url->setAbsolute();

    $this->drupalPostForm(NULL, ['type' => $url->toString()], $this->t('submit'));
    $this->assertResponse(200);
    $this->assertText('Displaying 3 search results');
  }

  /**
   * Tests the functionality of a widget to hide/show the item-count.
   */
  public function testLinksShowHideCount() {
    $id = 'links_widget';
    $name = '>.Facet &* Links';
    $facet_add_page = 'admin/config/search/facets/add-facet';
    $facet_edit_page = 'admin/config/search/facets/' . $id . '/display';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 9,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->drupalPostForm(NULL, ['widget' => 'links'], $this->t('Save'));

    $block_values = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->drupalPlaceBlock($block_values['plugin_id'], $block_values['settings']);

    // Go to the view and check that the facet links are shown with their
    // default settings.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['widget' => 'links', 'widget_configs[show_numbers]' => TRUE], $this->t('Save'));

    // Go back to the same view and check that links now display the count.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item (3)');
    $this->assertLink('article (2)');

    $this->drupalGet($facet_edit_page);
    $this->drupalPostForm(NULL, ['widget' => 'links', 'widget_configs[show_numbers]' => FALSE], $this->t('Save'));

    // The count should be hidden again.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('item');
    $this->assertLink('article');
  }

}
