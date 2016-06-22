<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Excludes unpublished nodes from node indexes.
 *
 * @SearchApiProcessor(
 *   id = "node_status",
 *   label = @Translation("Node status"),
 *   description = @Translation("Exclude unpublished nodes from node indexes."),
 *   stages = {
 *     "preprocess_index" = -50
 *   }
 * )
 */
class NodeStatus extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      if ($object instanceof NodeInterface && !$object->isPublished()) {
        unset($items[$item_id]);
      }
    }
  }

}
