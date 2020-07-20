<?php

namespace Drupal\social_auth_apple_extra\Controller;

use Drupal\social_auth_apple\Controller\AppleAuthController as AppleAuthControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AppleAuthExtraController.
 *
 * @package Drupal\social_auth_apple_extra\Controller
 */
class AppleAuthExtraController extends AppleAuthControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth_apple_extra.user_authenticator'),
      $container->get('social_auth_apple.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer')
    );
  }

}
