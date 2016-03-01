<?php

/**
 * @file
 * Contains \Drupal\address\AddressServiceProvider.
 */

namespace Drupal\address;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the country_manager service.
 */
class AddressServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('country_manager');
    $definition
      ->setClass('Drupal\address\Repository\CountryRepository')
      ->setArguments([new Reference('cache.default'), new Reference('language_manager')]);
  }

}
