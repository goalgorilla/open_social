<?php

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
   *   The value the field should have (or be related to by the operator). If
   *   $operator is "IN" or "NOT IN", $value has to be an array of values. If
   *   $operator is "BETWEEN", it has to be an array with exactly two values:
   *   the lower bound in key 0 and the upper bound in key 1 (both inclusive).
   *   Otherwise, $value must be a scalar.
   * @param string $operator
   *   The operator to use for checking the constraint. The following operators
   *   are always supported for primitive types: "=", "<>", "<", "<=", ">=",
   *   ">", "IN", "NOT IN", "BETWEEN". They have the same semantics as the
   *   corresponding SQL operators. Other operators might be added by backend
   *   features.
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
