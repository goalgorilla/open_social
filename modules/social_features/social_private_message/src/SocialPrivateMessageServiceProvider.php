<?php

namespace Drupal\social_private_message;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Social private message Base service provider implementation.
 *
 * @package Drupal\social_private_message
 */
class SocialPrivateMessageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('private_message.mapper');
    $definition->setClass('Drupal\social_private_message\Mapper\PrivateMessageMapper');
  }

}
