<?php

namespace Drupal\social_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SocialProfileEditProfileRedirectController.
 *
 * @package Drupal\social_profile\Controller
 */
class SocialProfileEditProfileRedirectController extends ControllerBase {

  /**
   * Redirects users from /edit-profile to edit profile page of current user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the edit profile page of current user.
   */
  public function editProfileRedirect(): RedirectResponse {
    return $this->redirect('profile.user_page.single', [
      'user' => $this->currentUser()->id(),
      'profile_type' => 'profile',
    ]);
  }

}
