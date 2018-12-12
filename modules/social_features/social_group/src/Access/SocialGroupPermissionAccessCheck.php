<?php

namespace Drupal\social_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on permissions defined via
 * $module.group_permissions.yml files.
 */
class SocialGroupPermissionAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialGroupPermissionAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $permission = $route->getRequirement('_social_group_permission');

    // Don't interfere if no permission was specified.
    if ($permission === NULL) {
      return AccessResult::neutral();
    }

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');

    if (!$group instanceof GroupInterface) {
      if (is_numeric($group)) {
        $group = $this->entityTypeManager->getStorage('group')->load($group);

        if (!$group instanceof GroupInterface) {
          return AccessResult::neutral();
        }
      }
      else {
        return AccessResult::neutral();
      }
    }

    // Allow to conjunct the permissions with OR ('+') or AND (',').
    $split = explode(',', $permission);

    if (count($split) > 1) {
      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $split, 'AND');
    }
    else {
      $split = explode('+', $permission);

      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $split, 'OR');
    }
  }

}
