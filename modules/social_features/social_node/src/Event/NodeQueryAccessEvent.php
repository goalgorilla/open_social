<?php

declare(strict_types=1);

namespace Drupal\social_node\Event;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\social_node\QueryAccess\SocialNodeEnsureTablesTrait;

/**
 * Defines node entity query alter event.
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
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The query cacheable metadata.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheableMetadata
   *   The query cacheable metadata.
   */
  public function __construct(
    protected SelectInterface $query,
    protected ConditionInterface $conditions,
    protected AccountProxy $account,
    protected CacheableMetadata $cacheableMetadata,
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
   * @return \Drupal\Core\Session\AccountProxy
   *   The user account.
   */
  public function account(): AccountProxy {
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

  /**
   * Returns the cache object.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cache object.
   */
  public function cacheableMetadata(): CacheableMetadata {
    return $this->cacheableMetadata;
  }

}
