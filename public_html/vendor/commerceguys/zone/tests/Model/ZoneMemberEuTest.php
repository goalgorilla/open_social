<?php

namespace CommerceGuys\Zone\Tests\Model;

use CommerceGuys\Zone\Model\ZoneMemberEu;

/**
 * @coversDefaultClass \CommerceGuys\Zone\Model\ZoneMemberEu
 */
class ZoneMemberEuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZoneMemberEu
     */
    protected $zoneMember;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->zoneMember = new ZoneMemberEu();
    }

    /**
     * @covers ::__construct
     *
     * @uses \CommerceGuys\Zone\Model\ZoneMemberEu::getName
     */
    public function testConstructor()
    {
        $this->assertEquals('EU', $this->zoneMember->getName());
    }

    /**
     * @covers ::match
     *
     * @uses \CommerceGuys\Zone\Model\ZoneMemberEu::__construct
     */
    public function testMatch()
    {
        $mockBuilder = $this
            ->getMockBuilder('CommerceGuys\Addressing\Model\Address')
            ->disableOriginalConstructor();

        $frenchAddress = $mockBuilder->getMock();
        $frenchAddress
            ->expects($this->any())
            ->method('getCountryCode')
            ->will($this->returnValue('FR'));
        $serbianAddress = $mockBuilder->getMock();
        $serbianAddress
            ->expects($this->any())
            ->method('getCountryCode')
            ->will($this->returnValue('RS'));

        $this->assertEquals(true, $this->zoneMember->match($frenchAddress));
        $this->assertEquals(false, $this->zoneMember->match($serbianAddress));
    }
}
