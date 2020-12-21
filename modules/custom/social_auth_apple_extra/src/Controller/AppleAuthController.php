<?php

namespace Drupal\social_auth_apple_extra\Controller;

use Drupal\social_auth_extra\Controller\AuthControllerBase;

/**
 * Returns responses for auth routes.
 *
 * @package Drupal\social_auth_apple_extra\Controller
 */
class AppleAuthController extends AuthControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'social_auth_apple';
  }

}
