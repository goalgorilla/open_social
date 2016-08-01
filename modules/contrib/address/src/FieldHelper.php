<?php

namespace Drupal\address;

use CommerceGuys\Addressing\Enum\AddressField;

/**
 * Provides property names and autocomplete attributes for AddressField values.
 */
class FieldHelper {

  /**
   * Gets the property name matching the given AddressField value.
   *
   * @param string $field
   *   An AddressField value.
   *
   * @return string
   *   The property name.
   */
  public static function getPropertyName($field) {
    $property_mapping = [
      AddressField::ADMINISTRATIVE_AREA => 'administrative_area',
      AddressField::LOCALITY => 'locality',
      AddressField::DEPENDENT_LOCALITY => 'dependent_locality',
      AddressField::POSTAL_CODE => 'postal_code',
      AddressField::SORTING_CODE => 'sorting_code',
      AddressField::ADDRESS_LINE1 => 'address_line1',
      AddressField::ADDRESS_LINE2 => 'address_line2',
      AddressField::ORGANIZATION => 'organization',
      AddressField::RECIPIENT => 'recipient',
    ];

    return isset($property_mapping[$field]) ? $property_mapping[$field] : NULL;
  }

  /**
   * Gets the autocomplete attribute for the given AddressField value.
   *
   * Source: https://html.spec.whatwg.org/multipage/forms.html#autofill
   *
   * @param string $field
   *   An AddressField value.
   *
   * @return string
   *   The autocomplete attribute.
   */
  public static function getAutocompleteAttribute($field) {
    $autocomplete_mapping = [
      AddressField::ADMINISTRATIVE_AREA => 'address-level1',
      AddressField::LOCALITY => 'address-level2',
      AddressField::DEPENDENT_LOCALITY => 'address-level3',
      AddressField::POSTAL_CODE => 'postal-code',
      AddressField::SORTING_CODE => 'sorting-code',
      AddressField::ADDRESS_LINE1 => 'address-line1',
      AddressField::ADDRESS_LINE2 => 'address-line2',
      AddressField::ORGANIZATION => 'organization',
      AddressField::RECIPIENT => 'name',
    ];

    return isset($autocomplete_mapping[$field]) ? $autocomplete_mapping[$field] : NULL;
  }

}
