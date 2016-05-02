<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility;

/**
 * Tests custom data types integration.
 *
 * @group search_api
 */
class CustomDataTypesTest extends KernelTestBase {

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test_backend',
    'user',
    'system',
    'entity_test',
    'text',
  );

  /**
   * An array of test entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installSchema('system', array('router'));
    $this->installSchema('user', array('users_data'));
    $this->installEntitySchema('entity_test');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    $this->installConfig(array('search_api_test_db'));

    // Create test entities.
    $this->entities[1] = EntityTest::create(array(
      'name' => 'foo bar baz föö smile' . json_decode('"\u1F601"'),
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('Orange', 'orange', 'örange', 'Orange'),
      'category' => 'item_category',
    ));
    $this->entities[2] = EntityTest::create(array(
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('strawberry', 'llama'),
      'category' => 'item_category',
    ));
    $this->entities[1]->save();
    $this->entities[2]->save();

    // Create a test server.
    $this->server = Server::create(array(
      'name' => 'Server test ~',
      'id' => 'test',
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    $this->index = Index::load('database_search_index');
    $this->index->setServer($this->server);
  }

  /**
   * Tests custom data types integration.
   */
  public function testCustomDataTypes() {
    $original_value = $this->entities[1]->get('name')->getValue()[0]['value'];
    $original_type = $this->index->getField('name')->getType();

    $item = $this->index->loadItem('entity:entity_test/1:en');
    $item = Utility::createItemFromObject($this->index, $item, 'entity:entity_test/1:en');

    $name_field = $item->getField('name');
    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();
    $label = $name_field->getLabel();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals($original_type, $processed_type, 'The processed type matches the original type.');
    $this->assertEquals('Name', $label, 'The label is correctly set.');

    // Reset the fields on the item and change to the supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $field = $this->index->getField('name')
      ->setType('search_api_test_data_type')
      ->setLabel("Test");
    $this->index->addField($field);

    $name_field = $item->getField('name');
    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals('search_api_test_data_type', $processed_type, 'The processed type matches the new type.');
    $this->assertEquals('Test', $name_field->getLabel(), 'The label is correctly set.');

    // Reset the fields on the item and change to the non-supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $field = $this->index->getField('name')
      ->setType('search_api_unsupported_test_data_type');
    $this->index->addField($field);
    $name_field = $item->getField('name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals('integer', $processed_type, 'The processed type matches the fallback type.');

    // Reset the fields on the item and change to the data altering data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields(array());
    $field = $this->index->getField('name')
      ->setType('search_api_altering_test_data_type');
    $this->index->addField($field);
    $name_field = $item->getField('name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals(strlen($original_value), $processed_value, 'The processed value matches the altered original value');
    $this->assertEquals('search_api_altering_test_data_type', $processed_type, 'The processed type matches the defined type.');
  }

}
