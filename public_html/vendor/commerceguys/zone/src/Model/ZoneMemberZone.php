<?php

namespace CommerceGuys\Zone\Model;

use CommerceGuys\Addressing\Model\AddressInterface;

/**
 * Matches a single zone.
 */
class ZoneMemberZone extends ZoneMember
{
    /**
     * The zone.
     *
     * @var ZoneInterface
     */
    protected $zone;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->zone->getName();
    }

    /**
     * Gets the zone.
     *
     * @return ZoneInterface The zone matched by the zone member.
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * Sets the zone.
     *
     * @param ZoneEntityInterface $zone The zone matched by the zone member.
     *
     * @return self
     */
    public function setZone(ZoneEntityInterface $zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function match(AddressInterface $address)
    {
        return $this->zone->match($address);
    }
}
