<?php

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Model\AddressInterface;

/**
 * Matches EU addresses.
 *
 * For performance reasons the list of EU countries is hardcoded, avoiding
 * the need to create and call 28 ZoneMemberCountry instances.
 *
 * @ZoneMember(
 *   id = "eu",
 *   name = @Translation("EU"),
 * )
 */
class ZoneMemberEu extends ZoneMemberBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name' => 'EU',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function match(AddressInterface $address) {
    $eu_countries = [
      'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB',
      'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT',
      'RO', 'SE', 'SI', 'SK',
    ];
    return in_array($address->getCountryCode(), $eu_countries);
  }

}
