<?php

namespace Drupal\address\Entity;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Enum\AdministrativeAreaType;
use CommerceGuys\Addressing\Enum\DependentLocalityType;
use CommerceGuys\Addressing\Enum\LocalityType;
use CommerceGuys\Addressing\Enum\PostalCodeType;
use CommerceGuys\Addressing\Model\FormatStringTrait;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the AddressFormat configuration entity.
 *
 * @ConfigEntityType(
 *   id = "address_format",
 *   label = @Translation("Address format"),
 *   handlers = {
 *    "access" = "Drupal\address\AddressFormatAccessControlHandler",
 *     "list_builder" = "Drupal\address\AddressFormatListBuilder",
 *     "storage" = "Drupal\address\AddressFormatStorage",
 *     "form" = {
 *       "add" = "Drupal\address\Form\AddressFormatForm",
 *       "edit" = "Drupal\address\Form\AddressFormatForm",
 *       "delete" = "Drupal\address\Form\AddressFormatDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer address formats",
 *   config_prefix = "address_format",
 *   entity_keys = {
 *     "id" = "countryCode",
 *     "label" = "countryCode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/regional/address-formats",
 *     "edit-form" = "/admin/config/regional/address-formats/manage/{address_format}",
 *     "delete-form" = "/admin/config/regional/address-formats/manage/{address_format}/delete"
 *   },
 *   config_export = {
 *     "countryCode",
 *     "format",
 *     "requiredFields",
 *     "uppercaseFields",
 *     "administrativeAreaType",
 *     "localityType",
 *     "dependentLocalityType",
 *     "postalCodeType",
 *     "postalCodePattern",
 *     "postalCodePrefix"
 *   }
 * )
 */
class AddressFormat extends ConfigEntityBase implements AddressFormatInterface {

  use FormatStringTrait;

  /**
   * The country code.
   *
   * @var string
   */
  protected $countryCode;

  /**
   * The required fields.
   *
   * @var array
   */
  protected $requiredFields = [];

  /**
   * The fields that need to be uppercased.
   *
   * @var array
   */
  protected $uppercaseFields = [];

  /**
   * The administrative area type.
   *
   * @var string
   */
  protected $administrativeAreaType;

  /**
   * The locality type.
   *
   * @var string
   */
  protected $localityType;

  /**
   * The dependent locality type.
   *
   * @var string
   */
  protected $dependentLocalityType;

  /**
   * The postal code type.
   *
   * @var string
   */
  protected $postalCodeType;

  /**
   * The postal code pattern.
   *
   * @var string
   */
  protected $postalCodePattern;

  /**
   * The postal code prefix.
   *
   * @var string
   */
  protected $postalCodePrefix;

  /**
   * Overrides \Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->countryCode;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::label().
   */
  public function label() {
    if ($this->countryCode == 'ZZ') {
      return t('Generic');
    }

    $countries = \Drupal::service('address.country_repository')->getList();
    if (isset($countries[$this->countryCode])) {
      $label = $countries[$this->countryCode];
    }
    else {
      $label = $this->countryCode;
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->countryCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode($country_code) {
    $this->countryCode = $country_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields() {
    return $this->requiredFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequiredFields(array $required_fields) {
    AddressField::assertAllExist($required_fields);
    $this->requiredFields = $required_fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUppercaseFields() {
    return $this->uppercaseFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setUppercaseFields(array $uppercase_fields) {
    AddressField::assertAllExist($uppercase_fields);
    $this->uppercaseFields = $uppercase_fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministrativeAreaType() {
    return $this->administrativeAreaType;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdministrativeAreaType($administrative_area_type) {
    AdministrativeAreaType::assertExists($administrative_area_type);
    $this->administrativeAreaType = $administrative_area_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalityType() {
    return $this->localityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocalityType($locality_type) {
    LocalityType::assertExists($locality_type);
    $this->localityType = $locality_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentLocalityType() {
    return $this->dependentLocalityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependentLocalityType($dependent_locality_type) {
    DependentLocalityType::assertExists($dependent_locality_type);
    $this->dependentLocalityType = $dependent_locality_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCodeType() {
    return $this->postalCodeType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCodeType($postal_code_type) {
    PostalCodeType::assertExists($postal_code_type);
    $this->postalCodeType = $postal_code_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCodePattern() {
    return $this->postalCodePattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCodePattern($postal_code_pattern) {
    $this->postalCodePattern = $postal_code_pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCodePrefix() {
    return $this->postalCodePrefix;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCodePrefix($postal_code_prefix) {
    $this->postalCodePrefix = $postal_code_prefix;
    return $this;
  }

}
