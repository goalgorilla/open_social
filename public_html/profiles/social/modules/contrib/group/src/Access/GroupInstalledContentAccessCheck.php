<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupInstalledContentAccessCheck.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
// @todo Follow up on https://www.drupal.org/node/2266817.
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on whether a content plugin is installed.
 */
class GroupInstalledContentAccessCheck implements AccessInterface {

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
    $access_string = $route->getRequirement('_group_installed_content');

    // Don't interfere if no plugin ID was specified.
    if ($access_string === NULL) {
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
      return AccessResult::neutral();
    }

    // We default to not granting access.
    $access = FALSE;

    // Allow to conjunct the plugin IDs with OR ('+') or AND (',').
    $plugin_ids = explode(',', $access_string);
    if (count($plugin_ids) > 1) {
      $access = TRUE;

      foreach ($plugin_ids as $plugin_id) {
        if (!$group->getGroupType()->hasContentPlugin($plugin_id)) {
          $access = FALSE;
          break;
        }
      }
    }
    else {
      $plugin_ids = explode('+', $access_string);
      foreach ($plugin_ids as $plugin_id) {
        if ($group->getGroupType()->hasContentPlugin($plugin_id)) {
          $access = TRUE;
          break;
        }
      }
    }

    return AccessResult::allowedIf($access);
  }

}
