<?php

namespace Drupal\search_api\Query;

/**
 * Represents a single (field operator value) condition in a search query.
 */
class Condition implements ConditionInterface {

  /**
   * The field this condition checks.
   *
   * @var string
   */
  protected $field;

  /**
   * The value being compared to the field.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The operator that combined field and value in this condition.
   *
   * @var string
   */
  protected $operator;

  /**
   * Constructs a Condition object.
   *
   * @param string $field
   *   The field this condition checks.
   * @param mixed $value
   *   The value being compared to the field.
   * @param string $operator
   *   (optional) The operator that combined field and value in this condition.
   */
  public function __construct($field, $value, $operator = '=') {
    $this->field = $field;
    $this->value = $value;
    $this->operator = $operator;
  }

  /**
   * {@inheritdoc}
   */
  public function getField() {
    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function setField($field) {
    $this->field = $field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value) {
    $this->value = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperator() {
    return $this->operator;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperator($operator) {
    $this->operator = $operator;
    return $this;
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    return "{$this->field} {$this->operator} " . str_replace("\n", "\n    ", var_export($this->value, TRUE));
  }

}
