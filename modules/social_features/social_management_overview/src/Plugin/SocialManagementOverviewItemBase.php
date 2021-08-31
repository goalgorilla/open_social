<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Social management overview item plugins.
 */
abstract class SocialManagementOverviewItemBase extends PluginBase implements SocialManagementOverviewItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): ?Url {
    if (isset($this->pluginDefinition['route'])) {
      return Url::fromRoute($this->pluginDefinition['route'], $this->getRouteParameters());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

}
