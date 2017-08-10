<?php

namespace Drupal\social_core;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Class SocialCoreServiceProvider.
 *
 * @package Drupal\social_core
 */
class SocialCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('entity.autocomplete_matcher');
    $definition->setClass('Drupal\social_core\Entity\EntityAutocompleteMatcher');
  }

}
