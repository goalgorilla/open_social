<?php

namespace Drupal\core_search_facets\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests integration of hooks.
 *
 * @group core_search_facets
 */
class HooksTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'search',
    'core_search_facets_test_hooks',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a field of type float.
    FieldStorageConfig::create(
      [
        'field_name' => 'float',
        'entity_type' => 'node',
        'type' => 'float',
      ]
    )->save();

    // Create an instance of the float field on the "page" content type.
    FieldConfig::create(
      [
        'field_name' => 'float',
        'entity_type' => 'node',
        'bundle' => 'page',
        'label' => 'Float Field Label',
      ]
    )->save();

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests various that all hooks fire correctly.
   */
  public function testHooks() {
    // Verify that hook_facets_core_allowed_field_types was triggered.
    $facet_add_page = 'admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertResponse(200);

    // Select the node_search facet source.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(
      NULL,
      ['facet_source_id' => 'core_node_search:node_search'],
      $this->t('Configure facet source')
    );

    // The field appears as expected.
    $this->assertText('Float Field Label', 'Float Field appears as expected');
  }

}
