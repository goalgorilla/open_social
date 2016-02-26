<?php

namespace CommerceGuys\Zone\Tests\Model;

use CommerceGuys\Zone\Model\ZoneMemberZone;

/**
 * @coversDefaultClass \CommerceGuys\Zone\Model\ZoneMemberZone
 */
class ZoneMemberZoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZoneMemberZone
     */
    protected $zoneMember;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->zoneMember = new ZoneMemberZone();
    }

    /**
     * @covers ::getName
     *
     * @uses \CommerceGuys\Zone\Model\ZoneMemberZone::setZone
     */
    public function testName()
    {
        $zone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->disableOriginalConstructor()
            ->getMock();
        $zone
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Test'));

        $this->zoneMember->setZone($zone);
        $this->assertEquals('Test', $this->zoneMember->getName());
    }

    /**
     * @covers ::getZone
     * @covers ::setZone
     */
    public function testZone()
    {
        $zone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->disableOriginalConstructor()
            ->getMock();

        $this->zoneMember->setZone($zone);
        $this->assertEquals($zone, $this->zoneMember->getZone());
    }

    /**
     * @covers ::match
     *
     * @uses \CommerceGuys\Zone\Model\ZoneMemberZone::setZone
     */
    public function testMatch()
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Model\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $matchingZone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->disableOriginalConstructor()
            ->getMock();
        $matchingZone
            ->expects($this->any())
            ->method('match')
            ->with($address)
            ->will($this->returnValue(true));
        $nonMatchingZone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->disableOriginalConstructor()
            ->getMock();
        $nonMatchingZone
            ->expects($this->any())
            ->method('match')
            ->with($address)
            ->will($this->returnValue(false));

        $this->zoneMember->setZone($matchingZone);
        $this->assertEquals(true, $this->zoneMember->match($address));

        $this->zoneMember->setZone($nonMatchingZone);
        $this->assertEquals(false, $this->zoneMember->match($address));
    }
}
