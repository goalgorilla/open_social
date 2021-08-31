<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an interface for Social management overview group plugins.
 */
interface SocialManagementOverviewGroupInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns label from plugin definition.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Returns list of group children.
   */
  public function getChildren(): array;

}
