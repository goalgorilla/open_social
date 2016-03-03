<?php

namespace CommerceGuys\Zone\Tests\Model;

use CommerceGuys\Zone\Model\Zone;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @coversDefaultClass \CommerceGuys\Zone\Model\Zone
 */
class ZoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Zone
     */
    protected $zone;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->zone = new Zone();
    }

    /**
     * @covers ::getId
     * @covers ::setId
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     */
    public function testId()
    {
        $this->zone->setId('north_america');
        $this->assertEquals('north_america', $this->zone->getId());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     * @covers ::__toString
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     */
    public function testName()
    {
        $this->zone->setName('North America');
        $this->assertEquals('North America', $this->zone->getName());
        $this->assertEquals('North America', (string) $this->zone);
    }

    /**
     * @covers ::getScope
     * @covers ::setScope
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     */
    public function testScope()
    {
        $this->zone->setScope('shipping');
        $this->assertEquals('shipping', $this->zone->getScope());
    }

    /**
     * @covers ::getPriority
     * @covers ::setPriority
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     */
    public function testPriority()
    {
        $this->zone->setPriority(10);
        $this->assertEquals(10, $this->zone->getPriority());
    }

    /**
     * @covers ::__construct
     * @covers ::getMembers
     * @covers ::setMembers
     * @covers ::hasMembers
     * @covers ::addMember
     * @covers ::removeMember
     * @covers ::hasMember
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     * @uses \CommerceGuys\Zone\Model\ZoneMember::setParentZone
     */
    public function testMembers()
    {
        $firstZoneMember = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\ZoneMember')
            ->getMock();
        $secondZoneMember = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\ZoneMember')
            ->getMock();
        $empty = new ArrayCollection();
        $members = new ArrayCollection([$firstZoneMember, $secondZoneMember]);

        $this->assertEquals(false, $this->zone->hasMembers());
        $this->assertEquals($empty, $this->zone->getMembers());
        $members = new ArrayCollection([$firstZoneMember, $secondZoneMember]);
        $this->zone->setMembers($members);
        $this->assertEquals($members, $this->zone->getMembers());
        $this->assertEquals(true, $this->zone->hasMembers());
        $this->zone->removeMember($secondZoneMember);
        $this->assertEquals(false, $this->zone->hasMember($secondZoneMember));
        $this->assertEquals(true, $this->zone->hasMember($firstZoneMember));
        $this->zone->addMember($secondZoneMember);
        $this->assertEquals($members, $this->zone->getMembers());
    }

    /**
     * @covers ::match
     *
     * @uses \CommerceGuys\Zone\Model\Zone::__construct
     * @uses \CommerceGuys\Zone\Model\Zone::setMembers
     */
    public function testMatch()
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Model\Address')
            ->getMock();
        $matchingZoneMember = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\ZoneMember')
            ->getMock();
        $matchingZoneMember
            ->expects($this->any())
            ->method('match')
            ->with($address)
            ->will($this->returnValue(true));
        $nonMatchingZoneMember = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\ZoneMember')
            ->getMock();
        $nonMatchingZoneMember
            ->expects($this->any())
            ->method('match')
            ->with($address)
            ->will($this->returnValue(false));

        $members = new ArrayCollection([$matchingZoneMember, $nonMatchingZoneMember]);
        $this->zone->setMembers($members);
        $this->assertEquals(true, $this->zone->match($address));

        $members = new ArrayCollection([$nonMatchingZoneMember]);
        $this->zone->setMembers($members);
        $this->assertEquals(false, $this->zone->match($address));
    }
}
