<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\address\Plugin\Field\FieldType\AddressItem;

/**
 * Type class for Address data.
 */
class Address {

  /**
   * Constructs the Address type.
   *
   * @param string|null $label
   *   The address label.
   * @param string|null $countryCode
   *   The country code.
   * @param string|null $administrativeArea
   *   The administrative area.
   * @param string|null $locality
   *   The locality.
   * @param string|null $dependentLocality
   *   The dependent locality.
   * @param string|null $postalCode
   *   The postal code.
   * @param string|null $sortingCode
   *   The sorting code.
   * @param string|null $addressLine1
   *   The address line 1.
   * @param string|null $addressLine2
   *   The address line 2.
   */
  public function __construct(
    public readonly ?string $label,
    public readonly ?string $countryCode,
    public readonly ?string $administrativeArea,
    public readonly ?string $locality,
    public readonly ?string $dependentLocality,
    public readonly ?string $postalCode,
    public readonly ?string $sortingCode,
    public readonly ?string $addressLine1,
    public readonly ?string $addressLine2,
  ) {}

  /**
   * Get formatted Address output.
   *
   * @param ?\Drupal\address\Plugin\Field\FieldType\AddressItem $item
   *   An address field item value.
   * @param ?string $label
   *   The location label.
   *
   * @return self
   *   The address data object.
   */
  public static function fromFieldItem(?AddressItem $item = NULL, ?string $label = NULL): self {
    $address = $item instanceof AddressItem ? $item->getValue() : NULL;

    return new self(
      label: $label ?? '',
      countryCode: $address['country_code'] ?? '',
      administrativeArea: $address['administrative_area'] ?? '',
      locality: $address['locality'] ?? '',
      dependentLocality: $address['dependent_locality'] ?? '',
      postalCode: $address['postal_code'] ?? '',
      sortingCode: $address['sorting_code'] ?? '',
      addressLine1: $address['address_line1'] ?? '',
      addressLine2: $address['address_line2'] ?? '',
    );
  }

}
