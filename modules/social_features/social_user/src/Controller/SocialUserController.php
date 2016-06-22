<?php

namespace Drupal\social_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Class SocialUserController.
 *
 * @package Drupal\social_user\Controller
 */
class SocialUserController extends ControllerBase {

  /**
   * OtherUserPage.
   *
   * @return RedirectResponse
   *   Return Redirect to the user account.
   */
  public function otherUserPage(UserInterface $user) {
    return $this->redirect('entity.user.canonical', array('user' => $user->id()));
  }

}
