<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesServiceProvider.
 */

namespace Drupal\features;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider implementation for Features to override config.installer.
 *
 * @ingroup container
 */
class FeaturesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the config.installer class with a new class.
    $definition = $container->getDefinition('config.installer');
    $definition->setClass('Drupal\features\FeaturesConfigInstaller');
  }

}
