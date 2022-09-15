<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

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
   * Array with values which need to be ignored.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0. Since this
   *   service is used only in hook_ENTITY_TYPE_access so specific entity type
   *   will be selected.
   *
   * @see https://www.drupal.org/node/3309659
   */
  public static function getIgnoredValues(): array;

  /**
   * NodeAccessCheck for given operation, node and user account.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0. Use
   *   process instead.
   *
   * @see https://www.drupal.org/node/3309659
   */
  public static function nodeAccessCheck(
    NodeInterface $node,
    string $operation,
    AccountInterface $account
  ): int;

  /**
   * NodeAccessCheck for given operation, node and user account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   */
  public function process(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): int;

  /**
   * Gets the Entity access for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0. Use check
   *   instead.
   *
   * @see https://www.drupal.org/node/3309659
   */
  public static function getEntityAccessResult(
    NodeInterface $node,
    string $operation,
    AccountInterface $account
  ): AccessResultInterface;

  /**
   * Gets access type to the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   */
  public function check(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): AccessResultInterface;

}
