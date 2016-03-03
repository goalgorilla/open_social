<?php

namespace CommerceGuys\Zone\Model;

/**
 * Default zone member implementation.
 *
 * Can be mapped and used by Doctrine.
 */
abstract class ZoneMember implements ZoneMemberEntityInterface
{
    /**
     * Zone member id.
     *
     * @var string
     */
    protected $id;

    /**
     * Zone member name.
     *
     * @var string
     */
    protected $name;

    /**
     * The parent zone.
     *
     * @var ZoneEntityInterface
     */
    protected $parentZone;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentZone()
    {
        return $this->parentZone;
    }

    /**
     * {@inheritdoc}
     */
    public function setParentZone(ZoneEntityInterface $parentZone = null)
    {
        $this->parentZone = $parentZone;

        return $this;
    }
}
