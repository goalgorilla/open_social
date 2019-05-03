<?php

namespace Drupal\social_magic_login\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MagicLoginController.
 */
class MagicLoginController extends ControllerBase {

  /**
   * Login.
   *
   * @return array
   *   Render array.
   */
  public function login($uid, $hash) {
    return [
      '#markup' => $this->t('Magic'),
    ];
  }
}
