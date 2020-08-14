<?php

namespace Drupal\social_core;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Drupal\social_core\Service\LayoutService;

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

    $definition = $container->getDefinition('select2.autocomplete_matcher');
    $definition->setClass('Drupal\social_core\Entity\Select2EntityAutocompleteMatcher');

    $modules = $container->getParameter('container.modules');

    // Check for installed layout_builder module.
    if (isset($modules['layout_builder'])) {
      // If it's installed we can register our service, using layout_builder
      // classes to check whether entities enabled layout builder.
      $service_definition = new Definition(LayoutService::class);
      $container->setDefinition('social_core.layout', $service_definition);
    }
  }

}
