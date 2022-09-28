<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

trait NodeTrait {

  /**
   * Get the node from a bundle and title.
   *
   * @param string $bundle
   *   The bundle of the node.
   * @param string $title
   *   The title of the node.
   *
   * @return int|null
   *   The integer ID of the node or NULL if no node could be found.
   */
  protected function getNodeIdFromTitle(string $bundle, string $title) : ?int {
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $bundle)
      ->condition('title', $title);

    $node_ids = $query->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);

    if (count($nodes) !== 1) {
      return NULL;
    }

    $node_id = (int) (reset($nodes)?->id());
    if ($node_id !== 0) {
      return $node_id;
    }

    return NULL;
  }

}
