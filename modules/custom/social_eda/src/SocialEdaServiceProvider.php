<?php

namespace Drupal\social_eda;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class SocialEdaServiceProvider.
 *
 * @package Drupal\social_eda
 */
class SocialEdaServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Check if the Dispatcher class exists.
    if ($container->hasDefinition('Drupal\social_eda_dispatcher\Dispatcher')) {
      // Alias the interface to the concrete class service if the class exists.
      $container->setAlias('Drupal\social_eda\DispatcherInterface', 'Drupal\social_eda_dispatcher\Dispatcher');
    }
  }

}
