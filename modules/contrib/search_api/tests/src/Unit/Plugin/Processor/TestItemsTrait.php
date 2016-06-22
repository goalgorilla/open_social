<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Utility;

/**
 * Provides common methods for test cases that need to create search items.
 */
trait TestItemsTrait {

  /**
   * The used item IDs for test items.
   *
   * @var string[]
   */
  protected $itemIds = array();

  /**
   * The class container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  protected $container;

  /**
   * Creates an array with a single item which has the given field.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that should be used for the item.
   * @param string $field_type
   *   The field type to set for the field.
   * @param mixed $field_value
   *   A field value to add to the field.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   (optional) A variable, passed by reference, into which the created field
   *   will be saved.
   * @param string $field_id
   *   (optional) The field ID to set for the field.
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   An array containing a single item with the specified field.
   */
  public function createSingleFieldItem(IndexInterface $index, $field_type, $field_value, FieldInterface &$field = NULL, $field_id = 'field_test') {
    $this->itemIds[0] = $item_id = Utility::createCombinedId('entity:node', '1:en');
    $item = Utility::createItem($index, $item_id);
    $field = Utility::createField($index, $field_id);
    $field->setType($field_type);
    $field->addValue($field_value);
    $item->setField($field_id, $field);
    $item->setFieldsExtracted(TRUE);

    return array($item_id => $item);
  }

  /**
   * Creates a certain number of test items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that should be used for the items.
   * @param int $count
   *   The number of items to create.
   * @param array[] $fields
   *   The fields to create on the items, with keys being combined property
   *   paths and values being arrays with properties to set on the field.
   * @param \Drupal\Core\TypedData\ComplexDataInterface|null $object
   *   (optional) The object to set on each item as the "original object".
   * @param array|null $datasource_ids
   *   (optional) An array of datasource IDs to use for the items, in that order
   *   (starting again from the front if necessary).
   *
   * @return \Drupal\search_api\Item\ItemInterface[]
   *   An array containing the requested test items.
   */
  public function createItems(IndexInterface $index, $count, array $fields, ComplexDataInterface $object = NULL, array $datasource_ids = array('entity:node')) {
    $datasource_count = count($datasource_ids);
    $items = array();
    for ($i = 0; $i < $count; ++$i) {
      $datasource_id = $datasource_ids[$i % $datasource_count];
      $this->itemIds[$i] = $item_id = Utility::createCombinedId($datasource_id, ($i + 1) . ':en');
      $item = Utility::createItem($index, $item_id);
      if (isset($object)) {
        $item->setOriginalObject($object);
      }
      foreach ($fields as $combined_property_path => $field_info) {
        list($field_info['datasource_id'], $field_info['property_path']) = Utility::splitCombinedId($combined_property_path);
        // Only add fields of the right datasource.
        if (isset($field_info['datasource_id']) && $field_info['datasource_id'] != $datasource_id) {
          continue;
        }
        $field_id = Utility::getNewFieldId($index, $field_info['property_path']);
        $field = Utility::createField($index, $field_id, $field_info);
        $item->setField($field_id, $field);
      }
      $item->setFieldsExtracted(TRUE);
      $items[$item_id] = $item;
    }
    return $items;
  }

  /**
   * Adds mock data type plugin manager and results cache services to \Drupal.
   */
  protected function setUpDataTypePlugin() {
    /** @var \Drupal\Tests\UnitTestCase|\Drupal\Tests\search_api\Unit\Plugin\Processor\TestItemsTrait $this */
    $data_type_plugin = $this->getMockBuilder('Drupal\search_api\DataType\DataTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $data_type_plugin->expects($this->any())
      ->method('getInstances')
      ->will($this->returnValue(array()));

    $results_static_cache = $this->getMockBuilder('Drupal\search_api\Query\ResultsCache')
      ->disableOriginalConstructor()
      ->getMock();
    $results_static_cache->expects($this->any())
      ->method('getResults')
      ->will($this->returnValue(array()));

    $this->container = new ContainerBuilder();
    $this->container->set('plugin.manager.search_api.data_type', $data_type_plugin);
    $this->container->set('search_api.results_static_cache', $results_static_cache);
    \Drupal::setContainer($this->container);
  }

}
