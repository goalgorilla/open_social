<?php

declare(strict_types=1);

namespace Drupal\social_group\Service;

use Drupal\group\Entity\GroupInterface;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;

/**
 * Determines the application's context relative to a group for OAuth tokens.
 *
 * Used when resolving group permissions from OAuth scopes: insider-scoped
 * permissions are only granted when the application is considered "inside"
 * the group. This interface is the jumping-off point for that logic; the
 * default implementation always returns FALSE (all applications are treated
 * as outsider). Future implementations can consider group membership or
 * other rules.
 *
 * When individual scope is resolved per group ID in
 * \Drupal\social_oauth\Decorator\GroupPermissionCheckerDecorator (instead of
 * returning no permissions), unskip the "individual scope, expected allowed"
 * scenarios in GroupPermissionCheckerOAuthScopeMatrixTest.
 */
interface ApplicationGroupContextInterface {

  /**
   * Whether the application (token) is considered insider of the group.
   *
   * When TRUE, insider-scoped permissions from the token's scopes are granted
   * for this group. When FALSE (default), only individual- and outsider-scoped
   * permissions apply.
   *
   * @param \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token
   *   The OAuth token (application acting as bot or on behalf of a user).
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check.
   *
   * @return bool
   *   TRUE if the application should be treated as insider of the group.
   */
  public function isApplicationInsiderForGroup(Oauth2TokenInterface $token, GroupInterface $group): bool;

}
