<?php

namespace CommerceGuys\Zone\Matcher;

use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Zone\Model\ZoneInterface;

interface ZoneMatcherInterface
{
    /**
     * Returns the best matching zone for the provided address.
     *
     * @param AddressInterface $address
     * @param string|null      $scope
     *
     * @return ZoneInterface|null
     */
    public function match(AddressInterface $address, $scope = null);

    /**
     * Returns all matching zones for the provided address.
     *
     * @param AddressInterface $address
     * @param string|null      $scope
     *
     * @return ZoneInterface[]
     */
    public function matchAll(AddressInterface $address, $scope = null);
}
