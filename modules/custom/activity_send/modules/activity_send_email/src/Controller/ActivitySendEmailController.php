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
   *
   * @deprecated in open_social:13.0.0 and is removed from open_social:14.0.0.
   * Use "\Drupal\social_email_broadcast\Controller\SocialEmailBroadcastController::userEditPage()"
   * instead.
   *
   * @see https://github.com/goalgorilla/open_social/pull/3905
   */
  public function userEditPage() {
    return $this->redirect('entity.user.edit_form', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
