<?php

namespace Drupal\social_auth_apple_extra\Controller;

use Drupal\social_auth_extra\Controller\SocialAuthExtraLinkControllerBase;

/**
 * Class AppleLinkController.
 *
 * @package Drupal\social_auth_apple_extra\Controller
 */
class AppleLinkController extends SocialAuthExtraLinkControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'social_auth_apple';
  }

}
