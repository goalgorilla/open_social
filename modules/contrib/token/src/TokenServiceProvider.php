<?php

/**
 * @file
 * Contains \Drupal\token\TokenServiceProvider.
 */

namespace Drupal\token;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replace core's token service with our own.
 */
class TokenServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('token');
    $definition->setClass('\Drupal\token\Token');
  }
}
