<?php

namespace Drupal\social_private_message;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Class SocialPrivateMessageServiceProvider.
 *
 * @package Drupal\social_private_message
 */
class SocialPrivateMessageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('private_message.mailer');
    $definition->setClass('Drupal\social_private_message\Service\PrivateMessageMailer');

    $definition = $container->getDefinition('private_message.mapper');
    $definition->setClass('Drupal\social_private_message\Mapper\PrivateMessageMapper');
  }

}
