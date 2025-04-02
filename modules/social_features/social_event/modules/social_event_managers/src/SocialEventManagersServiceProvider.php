<?php

namespace Drupal\social_event_managers;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\social_event_managers\Plugin\Group\RelationHandler\EventsGroupContentAccessControl;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters existing services for the Social Event Managers module.
 */
class SocialEventManagersServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['gnode'])) {
      // Decorating group relation handlers.
      $definition = new Definition(EventsGroupContentAccessControl::class);
      $definition->setArguments([
        new Reference('social_event_managers.relation_handler.access_control.group_node.decorator.inner'),
      ]);
      $definition->setPublic(FALSE);
      $definition->setShared(FALSE);
      $definition->setDecoratedService('group.relation_handler.access_control.group_node');
      $container->setDefinition('social_event_managers.relation_handler.access_control.group_node.decorator', $definition);
    }
  }

}
