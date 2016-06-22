<?php

namespace Drupal\search_api\Tests;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility;

/**
 * Contains helpers to create data that can be used by tests.
 */
trait ExampleContentTrait {

  /**
   * The generated test entities, keyed by ID.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $entities = array();

  /**
   * Sets up the necessary bundles on the test entity type.
   */
  protected function setUpExampleStructure() {
    entity_test_create_bundle('item');
    entity_test_create_bundle('article');
  }

  /**
   * Creates several test entities.
   */
  protected function insertExampleContent() {
    $count = \Drupal::entityQuery('entity_test')->count()->execute();

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    // To test Unicode compliance, include all kind of strange characters here.
    $smiley = json_decode('"\u1F601"');
    $this->entities[1] = $entity_test_storage->create(array(
      'name' => 'foo bar baz foobaz föö smile' . $smiley,
      'body' => 'test test case Case casE',
      'type' => 'item',
      'keywords' => array('Orange', 'orange', 'örange', 'Orange', $smiley),
      'category' => 'item_category',
    ));
    $this->entities[1]->save();
    $this->entities[2] = $entity_test_storage->create(array(
      'name' => 'foo test foobuz',
      'body' => 'bar test casE',
      'type' => 'item',
      'keywords' => array('orange', 'apple', 'grape'),
      'category' => 'item_category',
    ));
    $this->entities[2]->save();
    $this->entities[3] = $entity_test_storage->create(array(
      'name' => 'bar',
      'body' => 'test foobar Case',
      'type' => 'item',
    ));
    $this->entities[3]->save();
    $this->entities[4] = $entity_test_storage->create(array(
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => array('apple', 'strawberry', 'grape'),
      'category' => 'article_category',
      'width' => '1.0',
    ));
    $this->entities[4]->save();
    $this->entities[5] = $entity_test_storage->create(array(
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => array('orange', 'strawberry', 'grape', 'banana'),
      'category' => 'article_category',
      'width' => '2.0',
    ));
    $this->entities[5]->save();
    $count = \Drupal::entityQuery('entity_test')->count()->execute() - $count;
    $this->assertEqual($count, 5, "$count items inserted.");
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @param string $index_id
   *   The ID of the index on which items should be indexed.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems($index_id) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($index_id);
    return $index->indexItems();
  }

  /**
   * Returns the item IDs for the given entity IDs.
   *
   * @param array $entity_ids
   *   An array of entity IDs.
   *
   * @return string[]
   *   An array of item IDs.
   */
  protected function getItemIds(array $entity_ids) {
    $translate_ids = function ($entity_id) {
      return Utility::createCombinedId('entity:entity_test', $entity_id . ':en');
    };
    return array_map($translate_ids, $entity_ids);
  }

}
