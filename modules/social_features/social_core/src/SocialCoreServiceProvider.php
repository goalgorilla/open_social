<?php

namespace Drupal\social_core;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Drupal\social_core\Service\LayoutService;
use Symfony\Component\DependencyInjection\Reference;

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

    $modules = $container->getParameter('container.modules');
    if (is_array($modules)) {
      // Check if select 2 is installed before we get the definition, otherwise
      // you get a requested a non-existent service
      // "select2.autocomplete_matcher" on update hooks.
      if (isset($modules['select2'])) {
        $definition = $container->getDefinition('select2.autocomplete_matcher');
        $definition->setClass('Drupal\social_core\Entity\Select2EntityAutocompleteMatcher');
      }

      // Check for installed layout_builder module.
      if (isset($modules['layout_builder'])) {
        // If it's installed we can register our service, using layout_builder
        // classes to check whether entities enabled layout builder.
        $service_definition = new Definition(LayoutService::class);
        $container->setDefinition('social_core.layout', $service_definition);
      }

      // Check for installed social_event_invite module.
      if (isset($modules['social_event_invite'])) {
        // If the container has ginvite invitation services.
        if ($container->hasDefinition('social_event.status_helper')) {
          $definition = $container->getDefinition('social_core.invite');
          $definition->addArgument(new Reference('social_event.status_helper'));
        }
      }

      // Check for installed social_group_invite module.
      if (isset($modules['social_group_invite'])) {
        // If the container has ginvite invitation services.
        if ($container->hasDefinition('ginvite.invitation_loader')) {
          $definition = $container->getDefinition('social_core.invite');
          $definition->addArgument(new Reference('ginvite.invitation_loader'));
        }
      }
    }
  }

}
