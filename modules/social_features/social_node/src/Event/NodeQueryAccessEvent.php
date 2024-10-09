<?php

declare(strict_types=1);

namespace Drupal\social_node\Event;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_node\QueryAccess\SocialNodeEnsureTablesTrait;

/**
 * Defines the access by visibility conditions alter event.
 */
class NodeQueryAccessEvent {

  use SocialNodeEnsureTablesTrait;

  /**
   * Constructor for NodeQueryAccessVisibilityEvent object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The condition group.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *   The condition group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function __construct(
    protected SelectInterface $query,
    protected ConditionInterface $conditions,
    protected AccountInterface $account
  ) {}

  /**
   * Returns query conditions group for altering.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The conditions group.
   */
  public function getConditions(): ConditionInterface {
    return $this->conditions;
  }

  /**
   * Returns user account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account.
   */
  public function account(): AccountInterface {
    return $this->account;
  }

  /**
   * Returns the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query object.
   */
  public function query(): SelectInterface {
    return $this->query;
  }

}
