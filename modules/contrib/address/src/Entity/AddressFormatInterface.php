<?php

namespace Drupal\address\Entity;

use CommerceGuys\Addressing\Model\AddressFormatInterface as ExternalAddressFormatInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for address formats.
 *
 * The external address format interface contains getters, while this interface
 * adds matching setters.
 *
 * @see \CommerceGuys\Addressing\Model\AddressFormatInterface
 */
interface AddressFormatInterface extends ExternalAddressFormatInterface, ConfigEntityInterface {

  /**
   * Sets the two-letter country code.
   *
   * @param string $country_code
   *   The two-letter country code.
   *
   * @return $this
   */
  public function setCountryCode($country_code);

  /**
   * Sets the format string.
   *
   * @param string $format
   *   The format string.
   *
   * @return $this
   */
  public function setFormat($format);

  /**
   * Sets the list of required fields.
   *
   * @param array $required_fields
   *   An array of address fields.
   *
   * @return $this
   */
  public function setRequiredFields(array $required_fields);

  /**
   * Sets the list of fields that need to be uppercased.
   *
   * @param array $uppercase_fields
   *   An array of address fields.
   *
   * @return $this
   */
  public function setUppercaseFields(array $uppercase_fields);

  /**
   * Sets the administrative area type.
   *
   * @param string $administrative_area_type
   *   The administrative area type.
   *
   * @return $this
   */
  public function setAdministrativeAreaType($administrative_area_type);

  /**
   * Sets the locality type.
   *
   * @param string $locality_type
   *   The locality type.
   *
   * @return $this
   */
  public function setLocalityType($locality_type);

  /**
   * Sets the dependent locality type.
   *
   * @param string $dependent_locality_type
   *   The dependent locality type.
   *
   * @return $this
   */
  public function setDependentLocalityType($dependent_locality_type);

  /**
   * Sets the postal code type.
   *
   * @param string $postal_code_type
   *   The postal code type.
   *
   * @return $this
   */
  public function setPostalCodeType($postal_code_type);

  /**
   * Sets the postal code pattern.
   *
   * @param string $postal_code_pattern
   *   The postal code pattern.
   *
   * @return $this
   */
  public function setPostalCodePattern($postal_code_pattern);

  /**
   * Sets the postal code prefix.
   *
   * @param string $postal_code_prefix
   *   The postal code prefix.
   *
   * @return $this
   */
  public function setPostalCodePrefix($postal_code_prefix);

}
