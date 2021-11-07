<?php

namespace Drupal\social_group_invite;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Social Group Invite Service Provider.
 *
 * @package Drupal\social_group_invite
 */
class SocialGroupInviteServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // If ginvite is enabled, remove it's service which
    // sends a drupal set message on every page.
    if ($container->hasDefinition('ginvite_event_subscriber')) {
      $container->removeDefinition('ginvite_event_subscriber');
    }
  }

}
