<?php

namespace Drupal\search_api\Query;

/**
 * Represents a single (field operator value) condition in a search query.
 */
interface ConditionInterface {

  /**
   * Retrieves the field.
   *
   * @return string
   *   The field this condition checks.
   */
  public function getField();

  /**
   * Sets the field.
   *
   * @param string $field
   *   The new field.
   *
   * @return $this
   */
  public function setField($field);

  /**
   * Retrieves the value.
   *
   * @return mixed
   *   The value being compared to the field.
   */
  public function getValue();

  /**
   * Sets the value.
   *
   * @param mixed $value
   *   The new value.
   *
   * @return $this
   */
  public function setValue($value);

  /**
   * Retrieves the operator.
   *
   * @return string
   *   The operator that combined field and value in this condition.
   */
  public function getOperator();

  /**
   * Sets the operator.
   *
   * @param string $operator
   *   The new operator.
   *
   * @return $this
   */
  public function setOperator($operator);

}
