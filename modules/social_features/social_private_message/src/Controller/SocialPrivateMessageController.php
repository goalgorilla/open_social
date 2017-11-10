<?php

namespace Drupal\social_private_message\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Override the default controller for private messages.
 */
class SocialPrivateMessageController extends ControllerBase {

  /**
   * Override the default inbox page.
   */
  public function inbox() {
    return [];
  }

}
