<?php

namespace Drupal\facets\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\search_api\Entity\Index;

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

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test');
    $this->entities[1] = $entity_test_storage->create(array(
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => array('orange'),
      'category' => 'item_category',
    ));
    $this->entities[1]->save();
    $this->entities[2] = $entity_test_storage->create(array(
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => array('orange', 'apple', 'grape'),
      'category' => 'item_category',
    ));
    $this->entities[2]->save();
    $this->entities[3] = $entity_test_storage->create(array(
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
    ));
    $this->entities[3]->save();
    $this->entities[4] = $entity_test_storage->create(array(
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => array('apple', 'strawberry', 'grape'),
      'category' => 'article_category',
    ));
    $this->entities[4]->save();
    $this->entities[5] = $entity_test_storage->create(array(
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => array('orange', 'strawberry', 'grape', 'banana'),
      'category' => 'article_category',
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
   * Add a facet trough the UI.
   *
   * @param string $name
   *   The facet name.
   * @param string $id
   *   The facet id.
   * @param string $field
   *   The facet field.
   */
  protected function createFacet($name, $id, $field = 'type') {
    $facet_add_page = 'admin/config/search/facets/add-facet';

    $this->drupalGet($facet_add_page);

    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 2,
      'facet_source_id' => 'search_api_views:search_api_test_view:page_1',
      'facet_source_configs[search_api_views:search_api_test_view:page_1][field_identifier]' => $field,
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'search_api_views:search_api_test_view:page_1'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
  }

  /**
   * Asserts that a string is found before another string in the source.
   *
   * This uses the simpletest's getRawContent method to search in the source of
   * the page for the position of 2 strings and that the first argument is
   * before the second argument's position.
   *
   * @param string $x
   *   A string.
   * @param string $y
   *   Another string.
   */
  protected function assertStringPosition($x, $y) {
    $this->assertText($x);
    $this->assertText($y);

    $x_position = strpos($this->getRawContent(), $x);
    $y_position = strpos($this->getRawContent(), $y);

    $message = new FormattableMarkup('Assert that %x is before %y in the source', ['%x' => $x, '%y' => $y]);
    $this->assertTrue($x_position < $y_position, $message);
  }

}
