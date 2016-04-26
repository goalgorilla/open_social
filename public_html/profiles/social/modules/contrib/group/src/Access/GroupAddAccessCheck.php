<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupAddAccessCheck.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for group add pages.
 *
 * @ingroup group_access
 */
class GroupAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the group add page for the group type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type. If not specified, access is allowed if there
   *   exists at least one group type for which the user may create a group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(AccountInterface $account, GroupTypeInterface $group_type = NULL) {
    // If the user can bypass group access, return immediately.
    if ($account->hasPermission('bypass group access')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check whether the user can create a group of the provided type.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group');
    if ($group_type) {
      return $access_control_handler->createAccess($group_type->id(), $account, [], TRUE);
    }

    // Check whether the user can create a group of any type.
    foreach ($this->entityTypeManager->getStorage('group_type')->loadMultiple() as $group_type) {
      if ($access_control_handler->createAccess($group_type->id(), $account)) {
        return AccessResult::allowed();
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
