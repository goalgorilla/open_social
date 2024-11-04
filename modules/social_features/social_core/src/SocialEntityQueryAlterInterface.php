<?php

declare(strict_types=1);

namespace Drupal\social_core;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Interface for social_entity_query_alter plugins.
 */
interface SocialEntityQueryAlterInterface {

  /**
   * This would be the main place where we can alter the entity query.
   *
   * @todo This is not implemented yet.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The main query object.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *   The alterable condition group.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account against we should alter query.
   */
  public function entityQueryAccessAlter(SelectInterface $query, ConditionInterface $conditions, AccountInterface $account): void;

  /**
   * Contain a list of fields that should be indexed to perform search queries.
   *
   * @return array[]
   *   The list of fields associated by entity type.
   */
  public function searchApiFieldProperties(): array;

  /**
   * Add data to search api index.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *  The search api index item.
   */
  public function searchApiOnDataIndex(ItemInterface $item): void;

  /**
   * Alter search API queries.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search api query.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   * @param string $entity_type_id
   *   The entity type id that search api is processing.
   * @param string $datasource_id
   *   The affected search api datasource id.
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The search api index.
   */
  public function searchApiEntityQueryAlter(QueryInterface $query, ConditionGroupInterface $or, AccountInterface $account, string $entity_type_id, string $datasource_id, IndexInterface $search_api_index): void;

}
