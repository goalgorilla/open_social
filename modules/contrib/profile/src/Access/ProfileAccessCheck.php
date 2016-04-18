<?php

/**
 * @file
 * Contains \Drupal\profile\Access\ProfileAccessCheck.
 */

namespace Drupal\profile\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\profile\Entity\ProfileTypeInterface;

/**
 * Checks access to add, edit and delete profiles.
 */
class ProfileAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ProfileAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the profile add page for the profile type.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type entity.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, ProfileTypeInterface $profile_type = NULL) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('profile');

    if ($account->hasPermission('administer profile types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $operation = $route->getRequirement('_profile_access_check');
    if ($operation == 'add') {
      return $access_control_handler->access($profile_type, $operation, $account, TRUE);
    }

    if ($profile_type) {
      return $access_control_handler->createAccess($profile_type->id(), $account, [], TRUE);
    }
    // If checking whether a profile of any type may be created.
    foreach ($this->entityTypeManager->getStorage('profile_type')->loadMultiple() as $profile_type) {
      if (($access = $access_control_handler->createAccess($profile_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
