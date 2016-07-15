<?php

namespace Drupal\address\Event;

use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the available countries event.
 *
 * @see \Drupal\address\Event\AddressEvents
 * @see \Drupal\address\Plugin\Field\FieldType\AddressItem::getAvailableCountries
 */
class AvailableCountriesEvent extends Event {

  /**
   * The available countries.
   *
   * A list of country codes.
   *
   * @var array
   */
  protected $availableCountries;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Constructs a new AvailableCountriesEvent object.
   *
   * @param array $available_countries
   *   The available countries.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   */
  public function __construct(array $available_countries, FieldDefinitionInterface $field_definition) {
    $this->availableCountries = $available_countries;
    $this->fieldDefinition = $field_definition;
  }

  /**
   * Gets the available countries.
   *
   * @return array
   *   The available countries.
   */
  public function getAvailableCountries() {
    return $this->availableCountries;
  }

  /**
   * Sets the available countries.
   *
   * @param array $available_countries
   *   The available countries to set.
   *
   * @return $this
   */
  public function setAvailableCountries(array $available_countries) {
    $this->availableCountries = $available_countries;
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

