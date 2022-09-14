<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the entity access checker service interface.
 */
interface EntityAccessHelperInterface {

  /**
   * Neutral status.
   */
  public const NEUTRAL = 0;

  /**
   * Forbidden status.
   */
  public const FORBIDDEN = 1;

  /**
   * Allowed status.
   */
  public const ALLOW = 2;

  /**
   * Gets access type to the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view"
   *   - "update"
   *   - "delete"
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   */
  public function check(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): AccessResultInterface;

}
