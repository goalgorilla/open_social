<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;

/**
 * Defines an interface for Social management overview item plugins.
 */
interface SocialManagementOverviewItemInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns URL provided by plugin.
   */
  public function getUrl(): ?Url;

  /**
   * Returns parameters for route.
   */
  public function getRouteParameters(): array;

}
