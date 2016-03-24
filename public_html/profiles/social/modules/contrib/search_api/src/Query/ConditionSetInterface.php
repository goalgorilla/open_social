<?php

/**
 * @file
 * Contains Drupal\search_api\Query\ConditionSetInterface.
 */

namespace Drupal\search_api\Query;

/**
 * Defines a common interface for objects which can hold conditions.
 */
interface ConditionSetInterface {

  /**
   * Adds a new ($field $operator $value) condition.
   *
   * @param string $field
   *   The field to filter on, e.g. "title". The special field
   *   "search_api_datasource" can be used to filter by datasource ID.
   * @param mixed $value
   *   The value the field should have (or be related to by the operator).
   * @param string $operator
   *   The operator to use for checking the constraint. The following operators
   *   are supported for primitive types: "=", "<>", "<", "<=", ">=", ">". They
   *   have the same semantics as the corresponding SQL operators.
   *   If $field is a fulltext field, $operator can only be "=" or "<>", which
   *   are in this case interpreted as "contains" or "doesn't contain",
   *   respectively.
   *   If $value is NULL, $operator also can only be "=" or "<>", meaning the
   *   field must have no or some value, respectively.
   *
   * @return $this
   */
  public function addCondition($field, $value, $operator = '=');

  /**
   * Adds a nested condition group.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   A condition group that should be added.
   *
   * @return $this
   */
  public function addConditionGroup(ConditionGroupInterface $condition_group);

}
