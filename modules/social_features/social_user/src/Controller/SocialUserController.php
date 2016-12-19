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

  /**
   * The _title_callback for the users profile stream title.
   *
   * @return string
   *   The first and/or last name with the AccountName as a fallback.
   *
   */
  function setUserStreamTitle(UserInterface $user = NULL) {
    $accountname = '';
    // Let's get the First name Last name.
    /** @var \Drupal\profile\ProfileStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    if (!empty($storage)) {
      // Returns false.
      if ($user_profile = $storage->loadByUser($user, 'profile', TRUE)) {
        $accountname = trim(t('@first @last', array(
          '@first' => $user_profile->get('field_profile_first_name')->value,
          '@last' => $user_profile->get('field_profile_last_name')->value
        )));
      }
    }
    return $this->t($name = ($accountname !== '') ? $accountname : $user->getAccountName());
  }

}
