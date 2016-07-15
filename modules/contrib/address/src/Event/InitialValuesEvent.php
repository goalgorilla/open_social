<?php

namespace Drupal\address\Event;

use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the initial values event.
 *
 * @see \Drupal\address\Event\AddressEvents
 * @see \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget::getInitialValues()
 */
class InitialValuesEvent extends Event {

  /**
   * The initial values.
   *
   * @var array
   */
  protected $initialValues;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Constructs a new InitialValuesEvent object.
   *
   * @param array $initial_values
   *   The initial values.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   */
  public function __construct(array $initial_values, FieldDefinitionInterface $field_definition) {
    $this->initialValues = $initial_values;
    $this->fieldDefinition = $field_definition;
  }

  /**
   * Gets the initial values.
   *
   * @return array
   *   The initial values.
   */
  public function getInitialValues() {
    return $this->initialValues;
  }

  /**
   * Sets the initial values.
   *
   * @param array $initial_values
   *   The initial values to set.
   *
   * @return $this
   */
  public function setInitialValues(array $initial_values) {
    $this->initialValues = $initial_values;
    return $this;
  }

  /**
   * Gets the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}

