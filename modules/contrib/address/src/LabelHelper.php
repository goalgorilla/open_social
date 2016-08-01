<?php

namespace Drupal\address;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Enum\AdministrativeAreaType;
use CommerceGuys\Addressing\Enum\DependentLocalityType;
use CommerceGuys\Addressing\Enum\LocalityType;
use CommerceGuys\Addressing\Enum\PostalCodeType;
use Drupal\address\Entity\AddressFormatInterface;

/**
 * Provides translated labels for the library enums.
 */
class LabelHelper {

  /**
   * Gets the field labels suitable for the given address format.
   *
   * Intended to be shown to the end user, they sometimes use a more familiar
   * term than the field name (Company instead of Organization, Contact name
   * instead of Recipient, etc).
   *
   * @param \Drupal\address\Entity\AddressFormatInterface $address_format
   *   The address format.
   *
   * @return string[]
   *   An array of labels, keyed by field.
   */
  public static function getFieldLabels(AddressFormatInterface $address_format) {
    $administrative_area_type = $address_format->getAdministrativeAreaType();
    $locality_type = $address_format->getLocalityType();
    $dependent_locality_type = $address_format->getDependentLocalityType();
    $postal_code_type = $address_format->getPostalCodeType();

    return [
      AddressField::ADMINISTRATIVE_AREA => self::getAdministrativeAreaLabel($administrative_area_type),
      AddressField::LOCALITY => self::getLocalityLabel($locality_type),
      AddressField::DEPENDENT_LOCALITY => self::getDependentLocalityLabel($dependent_locality_type),
      AddressField::POSTAL_CODE => self::getPostalCodeLabel($postal_code_type),
      // Google's library always labels the sorting code field as "Cedex".
      AddressField::SORTING_CODE => t('Cedex', [], ['context' => 'Address label']),
      AddressField::ADDRESS_LINE1 => t('Street address', [], ['context' => 'Address label']),
      // The address line 2 label is usually shown only to screen-reader users.
      AddressField::ADDRESS_LINE2 => t('Street address line 2', [], ['context' => 'Address label']),
      AddressField::ORGANIZATION => t('Company', [], ['context' => 'Address label']),
      AddressField::RECIPIENT => t('Contact name', [], ['context' => 'Address label']),
    ];
  }

  /**
   * Gets the generic field labels.
   *
   * Intended primarily for backend settings screens.
   *
   * @return string[]
   *   The field labels, keyed by field.
   */
  public static function getGenericFieldLabels() {
    return [
      AddressField::ADMINISTRATIVE_AREA => t('Administrative area', [], ['context' => 'Address label']),
      AddressField::LOCALITY => t('Locality', [], ['context' => 'Address label']),
      AddressField::DEPENDENT_LOCALITY => t('Dependent locality', [], ['context' => 'Address label']),
      AddressField::POSTAL_CODE => t('Postal code', [], ['context' => 'Address label']),
      AddressField::SORTING_CODE => t('Sorting code', [], ['context' => 'Address label']),
      AddressField::ADDRESS_LINE1 => t('Address line 1', [], ['context' => 'Address label']),
      AddressField::ADDRESS_LINE2 => t('Address line 2', [], ['context' => 'Address label']),
      AddressField::ORGANIZATION => t('Organization', [], ['context' => 'Address label']),
      AddressField::RECIPIENT => t('Recipient', [], ['context' => 'Address label']),
    ];
  }

  /**
   * Gets the administrative area label for the given type.
   *
   * @param string $administrative_area_type
   *   The administrative area type.
   *
   * @return string
   *   The administrative area label.
   */
  public static function getAdministrativeAreaLabel($administrative_area_type) {
    if (!$administrative_area_type) {
      return NULL;
    }
    AdministrativeAreaType::assertExists($administrative_area_type);
    $labels = self::getAdministrativeAreaLabels();

    return $labels[$administrative_area_type];
  }

  /**
   * Gets all administrative area labels.
   *
   * @return string[]
   *   The administrative area labels, keyed by type.
   */
  public static function getAdministrativeAreaLabels() {
    return [
      AdministrativeAreaType::AREA => t('Area', [], ['context' => 'Address label']),
      AdministrativeAreaType::COUNTY => t('County', [], ['context' => 'Address label']),
      AdministrativeAreaType::DEPARTMENT => t('Department', [], ['context' => 'Address label']),
      AdministrativeAreaType::DISTRICT => t('District', [], ['context' => 'Address label']),
      AdministrativeAreaType::DO_SI => t('Do si', [], ['context' => 'Address label']),
      AdministrativeAreaType::EMIRATE => t('Emirate', [], ['context' => 'Address label']),
      AdministrativeAreaType::ISLAND => t('Island', [], ['context' => 'Address label']),
      AdministrativeAreaType::OBLAST => t('Oblast', [], ['context' => 'Address label']),
      AdministrativeAreaType::PARISH => t('Parish', [], ['context' => 'Address label']),
      AdministrativeAreaType::PREFECTURE => t('Prefecture', [], ['context' => 'Address label']),
      AdministrativeAreaType::PROVINCE => t('Province', [], ['context' => 'Address label']),
      AdministrativeAreaType::STATE => t('State', [], ['context' => 'Address label']),
    ];
  }

  /**
   * Gets the locality label for the given type.
   *
   * @param string $locality_type
   *   The locality type.
   *
   * @return string
   *   The locality label.
   */
  public static function getLocalityLabel($locality_type) {
    if (!$locality_type) {
      return NULL;
    }
    LocalityType::assertExists($locality_type);
    $labels = self::getLocalityLabels();

    return $labels[$locality_type];
  }

  /**
   * Gets all locality labels.
   *
   * @return string[]
   *   The locality labels, keyed by type.
   */
  public static function getLocalityLabels() {
    return [
      LocalityType::CITY => t('City', [], ['context' => 'Address label']),
      LocalityType::DISTRICT => t('District', [], ['context' => 'Address label']),
      LocalityType::POST_TOWN => t('Post town', [], ['context' => 'Address label']),
    ];
  }

  /**
   * Gets the dependent locality label for the given type.
   *
   * @param string $dependent_locality_type
   *   The dependent locality type.
   *
   * @return string
   *   The dependent locality label.
   */
  public static function getDependentLocalityLabel($dependent_locality_type) {
    if (!$dependent_locality_type) {
      return NULL;
    }
    DependentLocalityType::assertExists($dependent_locality_type);
    $labels = self::getDependentLocalityLabels();

    return $labels[$dependent_locality_type];
  }

  /**
   * Gets all dependent locality labels.
   *
   * @return string[]
   *   The dependent locality labels, keyed by type.
   */
  public static function getDependentLocalityLabels() {
    return [
      DependentLocalityType::DISTRICT => t('District', [], ['context' => 'Address label']),
      DependentLocalityType::NEIGHBORHOOD => t('Neighborhood', [], ['context' => 'Address label']),
      DependentLocalityType::VILLAGE_TOWNSHIP => t('Village township', [], ['context' => 'Address label']),
      DependentLocalityType::SUBURB => t('Suburb', [], ['context' => 'Address label']),
    ];
  }

  /**
   * Gets the postal code label for the given type.
   *
   * @param string $postal_code_type
   *   The postal code type.
   *
   * @return string
   *   The postal code label.
   */
  public static function getPostalCodeLabel($postal_code_type) {
    if (!$postal_code_type) {
      return NULL;
    }
    PostalCodeType::assertExists($postal_code_type);
    $labels = self::getPostalCodeLabels();

    return $labels[$postal_code_type];
  }

  /**
   * Gets all postal code labels.
   *
   * @return string[]
   *   The postal code labels, keyed by type.
   */
  public static function getPostalCodeLabels() {
    return [
      PostalCodeType::POSTAL => t('Postal code', [], ['context' => 'Address label']),
      PostalCodeType::ZIP => t('Zip code', [], ['context' => 'Address label']),
      PostalCodeType::PIN => t('Pin code', [], ['context' => 'Address label']),
    ];
  }

}
