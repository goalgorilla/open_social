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

    if (is_array($modules = $container->getParameter('container.modules'))) {
      // Check if select 2 is installed before we get the definition, otherwise
      // you get a requested a non-existent service
      // "select2.autocomplete_matcher" on update hooks.
      if (isset($modules['select2'])) {
        $definition = $container->getDefinition('select2.autocomplete_matcher');
        $definition->setClass('Drupal\social_core\Entity\Select2EntityAutocompleteMatcher');
      }
    }
  }

}
