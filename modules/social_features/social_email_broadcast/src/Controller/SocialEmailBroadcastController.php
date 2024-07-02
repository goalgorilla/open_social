<?php

namespace Drupal\social_email_broadcast\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller class for social email broadcast module.
 *
 * @package Drupal\social_email_broadcast\Controller
 */
class SocialEmailBroadcastController extends ControllerBase {

  /**
   * Redirect to user edit page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return Redirect to the user account.
   */
  public function userEditPage(): RedirectResponse {
    return $this->redirect('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
