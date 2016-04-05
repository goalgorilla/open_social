<?php

namespace Drupal\facets\Tests;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetSourceInterface;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class UrlIntegrationTest extends WebTestBase {

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
  public function testUrlIntegration() {
    $id = 'facet';
    $name = '&^Facet@#1';
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 3,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'type',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));

    $block_values = [
      'plugin_id' => 'facet_block:' . $id,
      'settings' => [
        'region' => 'footer',
        'id' => str_replace('_', '-', $id),
      ],
    ];
    $this->drupalPlaceBlock($block_values['plugin_id'], $block_values['settings']);

    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'facet:item']]);
    $this->checkClickedFacetUrl($url);

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::load($id);
    $this->assertTrue($facet instanceof FacetInterface);
    $config = $facet->getFacetSourceConfig();
    $this->assertTrue($config instanceof FacetSourceInterface);
    $this->assertEqual(NULL, $config->getFilterKey());

    $facet = NULL;
    $config = NULL;

    // Go to the only enabled facet source's config and change the filter key.
    $this->drupalGet('admin/config/search/facets');
    $this->clickLink($this->t('Configure'), 1);

    $edit = [
      'filter_key' => 'y',
      'url_processor' => 'query_string',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::load($id);
    $config = $facet->getFacetSourceConfig();
    $this->assertTrue($config instanceof FacetSourceInterface);
    $this->assertEqual('y', $config->getFilterKey());

    $facet = NULL;
    $config = NULL;

    $url_2 = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['y[0]' => 'facet:item']]);
    $this->checkClickedFacetUrl($url_2);

    // Go to the only enabled facet source's config and change the url
    // processor.
    $this->drupalGet('admin/config/search/facets');
    $this->clickLink($this->t('Configure'), 1);

    $edit = [
      'filter_key' => 'y',
      'url_processor' => 'dummy_query',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = Facet::load($id);
    $config = $facet->getFacetSourceConfig();
    $this->assertTrue($config instanceof FacetSourceInterface);
    $this->assertEqual('y', $config->getFilterKey());

    $facet = NULL;
    $config = NULL;

    $url_3 = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['y[0]' => 'facet||item']]);
    $this->checkClickedFacetUrl($url_3);
  }

  /**
   * Tests the url when a colon is used in the value.
   */
  public function testColonValue() {
    $id = 'water_bear';
    $url_alias = 'bear';
    $name = 'Water bear';

    $facet_add_page = 'admin/config/search/facets/add-facet';

    // Create the facet.
    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $url_alias,
      'name' => $name,
      'weight' => 1,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => 'keywords',
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));

    // Create / place the block.
    $block_settings = [
      'region' => 'footer',
      'id' => str_replace('_', '-', $id),
    ];
    $this->drupalPlaceBlock('facet_block:' . $id, $block_settings);

    // Add a new entity that has a colon in one of it's keywords.
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test');
    $entity_test_storage->create([
      'name' => 'Entity with colon',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange', 'test:colon'],
      'category' => 'item_category',
    ])->save();
    // Make sure the new item is indexed.
    $this->assertEqual(1, $this->indexItems($this->indexId));

    // Go to the overview and test that we have the expected links.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertLink('test:colon');
    $this->assertLink('orange');
    $this->assertLink('banana');

    // Click the link with the colon.
    $this->clickLink('test:colon');
    $this->assertResponse(200);

    // Make sure 'test:colon' is active.
    $url = Url::fromUserInput('/search-api-test-fulltext', ['query' => ['f[0]' => 'bear:test:colon']]);
    $this->assertUrl($url);
    $this->assertLink('(-) test:colon');
    $this->assertLink('orange');
    $this->assertLink('banana');
  }

  /**
   * Checks that the url after clicking a facet is as expected.
   *
   * @param \Drupal\Core\Url $url
   *   The expected url we end on.
   */
  protected function checkClickedFacetUrl(Url $url) {
    $this->drupalGet('search-api-test-fulltext');
    $this->assertResponse(200);
    $this->assertLink('item');
    $this->assertLink('article');

    $this->clickLink('item');

    $this->assertResponse(200);
    $this->assertLink('(-) item');
    $this->assertLink('article');
    $this->assertUrl($url);
  }

}
