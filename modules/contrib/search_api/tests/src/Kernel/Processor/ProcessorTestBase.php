<?php

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Utility;
use Drupal\system\Entity\Action;

/**
 * Provides a base class for Drupal unit tests for processors.
 */
abstract class ProcessorTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'user',
    'node',
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_backend',
    'comment',
    'text',
    'action',
    'system',
  );

  /**
   * The processor used for this test.
   *
   * @var \Drupal\search_api\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * The search index used for this test.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The search server used for this test.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * Performs setup tasks before each individual test method is run.
   *
   * Installs commonly used schemas and sets up a search server and an index,
   * with the specified processor enabled.
   *
   * @param string|null $processor
   *   (optional) The plugin ID of the processor that should be set up for
   *   testing.
   */
  public function setUp($processor = NULL) {
    parent::setUp();

    $this->installSchema('node', array('node_access'));
    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(array('field'));

    Action::create([
      'id' => 'foo',
      'label' => 'Foobaz',
      'plugin' => 'comment_publish_action',
    ])->save();

    \Drupal::configFactory()
      ->getEditable('search_api.settings')
      ->set('tracking_page_size', 100)
      ->save();

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    $this->server = Server::create(array(
      'id' => 'server',
      'name' => 'Server & Name',
      'status' => TRUE,
      'backend' => 'search_api_db',
      'backend_config' => array(
        'min_chars' => 3,
        'database' => 'default:default',
      ),
    ));
    $this->server->save();

    $this->index = Index::create(array(
      'id' => 'index',
      'name' => 'Index name',
      'status' => TRUE,
      'datasource_settings' => array(
        'entity:comment' => array(
          'plugin_id' => 'entity:comment',
          'settings' => array(),
        ),
        'entity:node' => array(
          'plugin_id' => 'entity:node',
          'settings' => array(),
        ),
      ),
      'server' => 'server',
      'tracker_settings' => array(
        'default' => array(
          'plugin_id' => 'default',
          'settings' => array(),
        ),
      ),
    ));
    $this->index->setServer($this->server);

    $field_subject = new Field($this->index, 'subject');
    $field_subject->setType('text');
    $field_subject->setPropertyPath('subject');
    $field_subject->setDatasourceId('entity:comment');
    $field_subject->setLabel('Subject');

    $field_title = new Field($this->index, 'title');
    $field_title->setType('text');
    $field_title->setPropertyPath('title');
    $field_title->setDatasourceId('entity:node');
    $field_title->setLabel('Title');

    $this->index->addField($field_subject);
    $this->index->addField($field_title);

    if ($processor) {
      /** @var \Drupal\search_api\Processor\ProcessorPluginManager $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.search_api.processor');
      $this->processor = $plugin_manager->createInstance($processor, array('index' => $this->index));
      $this->index->addProcessor($this->processor);
    }
    $this->index->save();
  }

  /**
   * Generates some test items.
   *
   * @param array[] $items
   *   Array of items to be transformed into proper search item objects. Each
   *   item in this array is an associative array with the following keys:
   *   - datasource: The datasource plugin ID.
   *   - item: The item object to be indexed.
   *   - item_id: The datasource-specific raw item ID.
   *   - *: Any other keys will be treated as property paths, and their values
   *     as a single value for a field with that property path.
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   The generated test items.
   */
  public function generateItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface[] $extracted_items */
    $extracted_items = array();
    foreach ($items as $item) {
      $id = Utility::createCombinedId($item['datasource'], $item['item_id']);
      $extracted_items[$id] = Utility::createItemFromObject($this->index, $item['item'], $id);
      foreach (array(NULL, $item['datasource']) as $datasource_id) {
        foreach ($this->index->getFieldsByDatasource($datasource_id) as $key => $field) {
          /** @var \Drupal\search_api\Item\FieldInterface $field */
          $field = clone $field;
          if (isset($item[$field->getPropertyPath()])) {
            $field->addValue($item[$field->getPropertyPath()]);
          }
          $extracted_items[$id]->setField($key, $field);
        }
      }
    }

    return $extracted_items;
  }

}
