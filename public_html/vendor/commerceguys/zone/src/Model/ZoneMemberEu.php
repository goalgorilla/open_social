<?php

namespace CommerceGuys\Zone\Model;

use CommerceGuys\Addressing\Model\AddressInterface;

/**
 * Matches the EU zone.
 *
 * For performance reasons the list of EU countries is hardcoded, avoiding
 * the need to create and call 28 ZoneMemberCountry instances.
 */
class ZoneMemberEu extends ZoneMember
{
    /**
     * Creates a ZoneMemberEu instance.
     */
    public function __construct()
    {
        $this->name = 'EU';
    }

    /**
     * {@inheritdoc}
     */
    public function match(AddressInterface $address)
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
            'FR', 'GB', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
            'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
        ];
        $countryCode = $address->getCountryCode();

        return in_array($countryCode, $euCountries);
    }
}
