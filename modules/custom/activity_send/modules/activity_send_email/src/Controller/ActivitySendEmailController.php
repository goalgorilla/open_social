<?php

namespace Drupal\activity_send_email\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for activity send email module.
 *
 * @package Drupal\activity_send_email\Controller
 */
class ActivitySendEmailController extends ControllerBase {

  /**
   * Redirect to user edit page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return Redirect to the user account.
   */
  public function userEditPage() {
    return $this->redirect('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
