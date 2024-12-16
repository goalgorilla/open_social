<?php

namespace Drupal\social_private_message;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\social_private_message\Mapper\PrivateMessageMapper;

/**
 * Social private message Base service provider implementation.
 *
 * @package Drupal\social_private_message
 */
class SocialPrivateMessageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $definition = $container->getDefinition('private_message.mapper');
    $definition->setClass(PrivateMessageMapper::class);
  }

}
