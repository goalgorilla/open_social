<?php

namespace CommerceGuys\Zone\Model;

use CommerceGuys\Addressing\Model\AddressInterface;

interface ZoneInterface
{
    /**
     * Gets the zone id.
     *
     * @return string The zone id.
     */
    public function getId();

    /**
     * Gets the zone name.
     *
     * @return string The zone name.
     */
    public function getName();

    /**
     * Gets the zone scope.
     *
     * @return string The zone scope.
     */
    public function getScope();

    /**
     * Gets the zone priority.
     *
     * Zones with higher priority will be matched first.
     *
     * @return int The zone priority.
     */
    public function getPriority();

    /**
     * Gets the zone members.
     *
     * @return ZoneMemberInterface[] The zone members.
     */
    public function getMembers();

    /**
     * Checks whether the zone has zone members.
     *
     * @return bool True if the zone has zone members, false otherwise.
     */
    public function hasMembers();

    /**
     * Checks whether the provided address belongs to the zone.
     *
     * @param AddressInterface $address The address.
     *
     * @return bool True if the provided address belongs to the zone,
     *              false otherwise.
     */
    public function match(AddressInterface $address);
}
