<?php

namespace Drupal\social_node;

use Drupal\node\NodeInterface;
use Drupal\node\NodeViewBuilder as NodeViewBuilderBase;

/**
 * Provides a NodeViewBuilder that has links that work with different languages.
 */
class NodeViewBuilder extends NodeViewBuilderBase {

  /**
   * {@inheritdoc}
   */
  protected static function buildLinks(NodeInterface $entity, $view_mode) {
    $build = parent::buildLinks($entity, $view_mode);

    // Remove the language argument from all links. It was added in Issue
    // #2149649 to solve the case where multiple translations in a single view
    // would link to the incorrect entity. However, this causes the issue in
    // Open Social that links change the interface language. Removing this
    // language argument allows the interface translation to remain the same
    // even when navigating to an entity that only exists in another language.
    foreach ($build['#links'] as &$link) {
      unset($link['language']);
    }

    return $build;

  }

}
