<?php

namespace Drupal\search_api\Tests;

use Drupal\search_api\Entity\Index;

/**
 * Tests the cacheability metadata of Search API.
 *
 * @group search_api
 */
class CacheabilityTest extends WebTestBase {

  use ExampleContentTrait;

  /**
   * The ID of the search server used for this test.
   *
   * @var string
   */
  protected $serverId;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'search_api',
    'search_api_test_backend',
    'search_api_test_views',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add a test server and index.
    $this->getTestServer();
    $this->getTestIndex();

    // Set up example structure and content and populate the test index with
    // that content.
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->indexItems($this->indexId);
  }

  /**
   * Tests the cacheability settings of Search API.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    // Verify that the search results are marked as uncacheable.
    $this->drupalGet('search-api-test');
    $this->assertResponse(200);
    $this->assertHeader('x-drupal-dynamic-cache', 'UNCACHEABLE');
    $this->assertTrue(strpos($this->drupalGetHeader('cache-control'), 'no-cache'));
  }

}
