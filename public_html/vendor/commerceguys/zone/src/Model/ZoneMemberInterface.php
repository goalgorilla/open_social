<?php

namespace CommerceGuys\Zone\Model;

use CommerceGuys\Addressing\Model\AddressInterface;

interface ZoneMemberInterface
{
    /**
     * Gets the zone member id.
     *
     * @return string The zone member id.
     */
    public function getId();

    /**
     * Gets the zone member name.
     *
     * @return string The zone member name.
     */
    public function getName();

    /**
     * Gets the parent zone.
     *
     * @return ZoneInterface|null The parent zone, if set.
     */
    public function getParentZone();

    /**
     * Checks whether the provided address belongs to the zone member.
     *
     * @param AddressInterface $address The address.
     *
     * @return bool True if the provided address belongs to the zone member,
     *              false otherwise.
     */
    public function match(AddressInterface $address);
}
