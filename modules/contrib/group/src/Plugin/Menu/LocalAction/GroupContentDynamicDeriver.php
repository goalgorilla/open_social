<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Menu\LocalAction\GroupContentDynamicDeriver.
 */

namespace Drupal\group\Plugin\Menu\LocalAction;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local actions for group content pages.
 */
class GroupContentDynamicDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupContentDynamicDeriver.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(GroupContentEnablerManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Retrieve all installed content enabler plugins.
    $installed = $this->pluginManager->getInstalledIds();

    // Retrieve all possible collection route names from all installed plugins.
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
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
