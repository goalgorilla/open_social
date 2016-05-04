<?php

/**
 * @file
 * Contains \Drupal\gnode\Plugin\GroupContentEnabler\GroupNodeDeriver.
 */

namespace Drupal\gnode\Plugin\GroupContentEnabler;

use Drupal\node\Entity\NodeType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupNodeDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $label = $node_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group node') . " ($label)",
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
