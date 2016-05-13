<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupContentAddAccessCheck.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group content creation.
 */
class GroupContentAddAccessCheck implements AccessInterface {

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
   * Checks access for group content creation routes.
   *
   * All routes using this access check should have a group parameter and have
   * the group content plugin set in the _group_content_add_access requirement.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   (optional) The group type. If not specified, access is allowed if there
   *   exists at least one group type for which the user may create a group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    $group_content_enabler = $route->getRequirement('_group_content_add_access');

    // We can only get the group content type ID if the plugin is installed.
    if ($group->getGroupType()->hasContentPlugin($group_content_enabler)) {
      $group_content_type_id = $group->getGroupType()->getContentPlugin($group_content_enabler)->getContentTypeConfigId();
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_content');
      return $access_control_handler->createAccess($group_content_type_id, $account, ['group' => $group], TRUE);
    }

    return AccessResult::forbidden();
  }

}
