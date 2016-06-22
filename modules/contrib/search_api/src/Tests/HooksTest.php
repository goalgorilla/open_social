<?php

namespace Drupal\search_api\Tests;

/**
 * Tests integration of hooks.
 *
 * @group search_api
 */
class HooksTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'node',
    'search_api',
    'search_api_test_backend',
    'search_api_test_views',
    'search_api_test_hooks',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create some nodes.
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 1'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 2'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 3'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 4'));

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Create an index and server to work with.
    $this->getTestServer();
    $this->getTestIndex();

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests various operations via the Search API's admin UI.
   */
  public function testHooks() {
    // hook_search_api_backend_info_alter was triggered.
    $this->drupalGet('admin/config/search/search-api/add-server');
    $this->assertText('Slims return');

    // hook_search_api_datasource_info_alter was triggered.
    $this->drupalGet('admin/config/search/search-api/add-index');
    $this->assertText('Distant land');

    // hook_search_api_processor_info_alter was triggered.
    $this->drupalGet($this->getIndexPath('processors'));
    $this->assertText('Mystic bounce');

    $this->drupalGet($this->getIndexPath());
    $this->drupalPostForm(NULL, [], $this->t('Index now'));

    // hook_search_api_index_items_alter was triggered, this removed node:1.
    // hook_search_api_query_TAG_alter was triggered, this removed node:3.
    $this->assertText('There are 2 items indexed on the server for this index.');
    $this->assertText('Successfully indexed 4 items.');
    $this->assertText('Stormy');

    // hook_search_api_items_indexed was triggered.
    $this->assertText('Please set me at ease');

    // hook_search_api_index_reindex was triggered.
    $this->drupalGet($this->getIndexPath('reindex'));
    $this->drupalPostForm(NULL, [], $this->t('Confirm'));
    $this->assertText('Montara');

    // hook_search_api_data_type_info_alter was triggered.
    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertText('Peace/Dolphin dance');
    // The implementation of hook_search_api_field_type_mapping_alter has
    // removed all dates, so we can't see any timestamp anymore in the page.
    $url_options['query']['datasource'] = 'entity:node';
    $this->drupalGet($this->getIndexPath('fields/add'), $url_options);
    $this->assertNoText('timestamp');

    $this->drupalGet('search-api-test');
    // hook_search_api_query_alter was triggered.
    $this->assertText('Funky blue note');
    // hook_search_api_results_alter was triggered.
    $this->assertText('Stepping into tomorrow');
    // hook_search_api_results_TAG_alter was triggered.
    $this->assertText('Llama');
  }

}
