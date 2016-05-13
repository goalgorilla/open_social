<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Menu\LocalAction\GroupContentDynamicDeriver.
 */

namespace Drupal\group\Plugin\Menu\LocalAction;

use Drupal\group\Plugin\GroupContentEnablerHelper;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local actions for group content pages.
 */
class GroupContentDynamicDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Retrieve all installed content enabler plugins.
    $installed = GroupContentEnablerHelper::getInstalledContentEnablerIDs();

    // Retrieve all possible collection route names from all installed plugins.
    foreach (GroupContentEnablerHelper::getAllContentEnablers() as $plugin_id => $plugin) {
      // Skip plugins that have not been installed anywhere.
      if (!in_array($plugin_id, $installed)) {
        continue;
      }

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      foreach ($plugin->getLocalActions() as $action_id => $local_action) {
        $this->derivatives[$action_id] = $local_action + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
