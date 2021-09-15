<?php

namespace Drupal\social_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Allow a user's profile to be viewed.
 */
class UserProfileController extends ControllerBase {

  /**
   * Builds a page title for the profile.
   *
   * @return string
   *   The page title.
   */
  public function title() {
    return new TranslatableMarkup("Information");
  }

  /**
   * Builds the add/edit page for "single" profile types.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   *
   * @return array
   *   The response.
   */
  public function view(UserInterface $user, ProfileTypeInterface $profile_type) {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    $profile_type_id = $profile_type->id();
    if (!is_string($profile_type_id)) {
      throw new \RuntimeException("Profile type with non-string ID was provided.");
    }
    $profile = $profile_storage->loadByUser($user, $profile_type_id);

    if (!$profile) {
      throw new NotFoundHttpException();
    }

    return $this->entityTypeManager()->getViewBuilder('profile')->view($profile);
  }

  /**
   * Checks access for the single/multiple pages.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(UserInterface $user, ProfileTypeInterface $profile_type, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResult $user_access */
    $user_access = $user->access('view', $account, TRUE);
    if (!$user_access->isAllowed()) {
      // The account does not have access to the user's canonical page
      // ("/user/{user}"), don't allow access to any sub-pages either.
      return $user_access;
    }

    $access_control_handler = $this->entityTypeManager()
      ->getAccessControlHandler('profile');
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    $profile_type_id = $profile_type->id();
    if (!is_string($profile_type_id)) {
      throw new \RuntimeException("Profile type with non-string ID was provided.");
    }
    $profile = $profile_storage->loadByUser($user, $profile_type_id);

    // If the target user has no profile then we test against a stub. This
    // ensures we don't leak non-existant profiles for existing users to anyone
    // who is not allowed to view any profiles.
    if ($profile === NULL) {
      $profile = $profile_storage->create([
        'type' => $profile_type_id,
        'uid' => $user->id(),
      ]);
    }

    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $access_control_handler->access($profile, 'view', $account, TRUE);
    return $access;
  }

}
