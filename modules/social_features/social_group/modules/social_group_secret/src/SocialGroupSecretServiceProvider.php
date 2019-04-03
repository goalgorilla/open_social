<?php

namespace Drupal\social_group_secret;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\social_group_secret\EventSubscriber\SocialGroupSecretSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SocialGroupSecretServiceProvider.
 *
 * Turn off redirecting to the login page from the secret group page when the
 * user does not have access to the group.
 *
 * @package Drupal\social_group_secret
 */
class SocialGroupSecretServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('r4032login.subscriber')
      ->setClass(SocialGroupSecretSubscriber::class)
      ->addArgument(new Reference('current_route_match'))
      ->addArgument(new Reference('exception.custom_page_html'))
      ->addArgument(new Reference('exception.default_html'));
  }

}
