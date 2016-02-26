<?php

namespace CommerceGuys\Zone\Tests\Model;

/**
 * @coversDefaultClass \CommerceGuys\Zone\Model\ZoneMember
 */
class ZoneMemberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZoneMember
     */
    protected $zoneMember;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->zoneMember = $this->getMockForAbstractClass('\CommerceGuys\Zone\Model\ZoneMember');
    }

    /**
     * @covers ::getId
     * @covers ::setId
     */
    public function testId()
    {
        $this->zoneMember->setId('fr_tax');
        $this->assertEquals('fr_tax', $this->zoneMember->getId());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     */
    public function testName()
    {
        $this->zoneMember->setName('France');
        $this->assertEquals('France', $this->zoneMember->getName());
    }

    /**
     * @covers ::getParentZone
     * @covers ::setParentZone
     */
    public function testParentZone()
    {
        $zone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->disableOriginalConstructor()
            ->getMock();

        $this->zoneMember->setParentZone($zone);
        $this->assertEquals($zone, $this->zoneMember->getParentZone());
    }
}
