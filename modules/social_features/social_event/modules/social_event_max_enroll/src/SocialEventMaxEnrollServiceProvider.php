<?php

namespace Drupal\social_event_max_enroll;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SocialEventMaxEnrollServiceProvider.
 *
 * @package Drupal\social_event_max_enroll
 */
class SocialEventMaxEnrollServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('social_event_an_enroll.overrider')) {
      $container
        ->getDefinition('social_event_max_enroll.overrider')
        ->addArgument(new Reference('social_event_an_enroll.overrider'));
    }
  }

}
