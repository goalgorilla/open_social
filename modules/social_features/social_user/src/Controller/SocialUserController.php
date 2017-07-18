<?php

namespace Drupal\social_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

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
   */
  public function setUserStreamTitle(UserInterface $user = NULL) {
    if ($user instanceof UserInterface) {
      return $user->getDisplayName();
    }
  }
  
  /**
   * Checks access for a user list page request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    // Check standart permission and our custom.
    return AccessResult::allowedIfHasPermissions($account, array('administer users', 'view users'), 'OR');
  }

}
