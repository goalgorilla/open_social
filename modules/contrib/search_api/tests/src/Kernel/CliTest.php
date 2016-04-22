<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

/**
 * Tests Search API functionality when executed in the CLI.
 *
 * @group search_api
 */
class CliTest extends KernelTestBase {

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'search_api',
    'search_api_test_backend',
    'user',
    'system',
    'entity_test',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installEntitySchema('entity_test');

    // Create a test server.
    $this->server = Server::create(array(
      'name' => 'Test server',
      'id' => 'test',
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    // Manually set the tracking page size since the module's default
    // configuration is not installed automatically in kernel tests.
    \Drupal::configFactory()
      ->getEditable('search_api.settings')
      ->set('tracking_page_size', 100)
      ->save();
    // Disable the use of batches for item tracking to simulate a CLI
    // environment.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
  }

  /**
   * Tests tracking of items when saving an index through the CLI.
   */
  public function testItemTracking() {
    EntityTest::create(array(
      'name' => 'foo bar baz föö smile' . json_decode('"\u1F601"'),
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('Orange', 'orange', 'örange', 'Orange'),
      'category' => 'item_category',
    ))->save();
    EntityTest::create(array(
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('strawberry', 'llama'),
      'category' => 'item_category',
    ))->save();

    // Create a test index.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::create(array(
      'name' => 'Test index',
      'id' => 'index',
      'status' => 1,
      'datasource_settings' => array(
        'entity:entity_test' => array(
          'plugin_id' => 'entity:entity_test',
          'settings' => array(),
        ),
      ),
      'tracker_settings' => array(
        'default' => array(
          'plugin_id' => 'default',
          'settings' => array(),
        ),
      ),
      'server' => $this->server->id(),
      'options' => array('index_directly' => TRUE),
    ));
    $index->save();

    $total_items = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed_items = $index->getTrackerInstance()->getIndexedItemsCount();

    $this->assertEquals(2, $total_items, 'The 2 items are tracked.');
    $this->assertEquals(0, $indexed_items, 'No items are indexed');

    EntityTest::create(array(
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('strawberry', 'llama'),
      'category' => 'item_category',
    ))->save();
    EntityTest::create(array(
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('strawberry', 'llama'),
      'category' => 'item_category',
    ))->save();

    $total_items = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed_items = $index->getTrackerInstance()->getIndexedItemsCount();

    $this->assertEquals(4, $total_items, 'All 4 items are tracked.');
    $this->assertEquals(2, $indexed_items, '2 items are indexed');
  }

}
