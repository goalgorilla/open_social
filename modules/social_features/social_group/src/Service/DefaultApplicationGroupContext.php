<?php

declare(strict_types=1);

namespace Drupal\social_group\Service;

use Drupal\group\Entity\GroupInterface;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;

/**
 * Default implementation: application is never considered insider of a group.
 *
 * All applications are treated as outsider for permission checks.
 * Insider-scoped OAuth permissions are therefore not granted. This can be
 * replaced or decorated to implement "application inside a group" logic later.
 */
class DefaultApplicationGroupContext implements ApplicationGroupContextInterface {

  /**
   * {@inheritdoc}
   */
  public function isApplicationInsiderForGroup(Oauth2TokenInterface $token, GroupInterface $group): bool {
    return FALSE;
  }

}
