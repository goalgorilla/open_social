<?php

namespace Drupal\social_event_invite;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\social_event_invite\Service\SocialEventInviteEntityAccessHelper;

/**
 * Defines a service provider for the Social Event Invite Enrolments module.
 */
class SocialEventInviteServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $container->getDefinition('entity_access_by_field.helper')
      ->setClass(SocialEventInviteEntityAccessHelper::class);
  }

}
