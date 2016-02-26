<?php

namespace CommerceGuys\Addressing\Tests\Repository;

use CommerceGuys\Addressing\Repository\SubdivisionRepository;
use CommerceGuys\Zone\Model\Zone;
use CommerceGuys\Zone\Repository\ZoneRepository;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \CommerceGuys\Zone\Repository\ZoneRepository
 */
class ZoneRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Known zones.
     *
     * @var array
     */
    protected $zones = [
        'de' => [
            'name' => 'Germany',
            'scope' => 'shipping',
            'members' => [
                [
                    'type' => 'country',
                    'id' => '1',
                    'name' => 'Germany',
                    'country_code' => 'DE',
                ],
            ],
        ],
        'de_vat' => [
            'name' => 'Germany',
            'scope' => 'tax',
            'priority' => 1,
            // A real zone wouldn't reference a zone of a different
            // scope (like here with de_vat -> de), but it decreases the
            // amount of data in this test.
            'members' => [
                [
                    'type' => 'zone',
                    'id' => '2',
                    'zone' => 'de',
                ],
                [
                    'type' => 'country',
                    'id' => '3',
                    'name' => 'Austria',
                    'country_code' => 'AT',
                    'included_postal_codes' => '6691, 6991:6993',
                    // Dummy data to ensure all fields get tested.
                    'administrative_area' => 'CH-AG',
                    'locality' => 'CH-AG',
                    'dependent_locality' => 'CH-AG',
                    'excluded_postal_codes' => '123456',
                ],
            ],
        ],
    ];

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        // Mock the existence of JSON definitions on the filesystem.
        $root = vfsStream::setup('resources');
        $directory = vfsStream::newDirectory('zone')->at($root);
        foreach ($this->zones as $id => $definition) {
            $filename = $id . '.json';
            vfsStream::newFile($filename)->at($directory)->setContent(json_encode($definition));
        }

        // Instantiate the zone repository and confirm that the
        // definition path was properly set.
        $zoneRepository = new ZoneRepository('vfs://resources/zone/');
        $definitionPath = $this->getObjectAttribute($zoneRepository, 'definitionPath');
        $this->assertEquals('vfs://resources/zone/', $definitionPath);

        return $zoneRepository;
    }

    /**
     * @covers ::get
     * @covers ::loadDefinition
     * @covers ::createZoneFromDefinition
     * @covers ::createZoneMemberCountryFromDefinition
     * @covers ::createZoneMemberZoneFromDefinition
     *
     * @uses \CommerceGuys\Zone\Model\Zone
     * @uses \CommerceGuys\Zone\Model\ZoneMember
     * @uses \CommerceGuys\Zone\Model\ZoneMemberCountry
     * @uses \CommerceGuys\Zone\Model\ZoneMemberZone
     * @uses \CommerceGuys\Zone\PostalCodeHelper
     * @depends testConstructor
     */
    public function testGet($zoneRepository)
    {
        $zone = $zoneRepository->get('de_vat');
        $this->assertInstanceOf('CommerceGuys\Zone\Model\Zone', $zone);
        $this->assertEquals('de_vat', $zone->getId());
        $this->assertEquals('Germany', $zone->getName());
        $this->assertEquals('tax', $zone->getScope());
        $this->assertEquals('1', $zone->getPriority());
        $members = $zone->getMembers();
        $this->assertCount(2, $members);

        $germanyMember = $members[0];
        $this->assertInstanceOf('CommerceGuys\Zone\Model\ZoneMemberZone', $germanyMember);
        $this->assertEquals('2', $germanyMember->getId());
        $this->assertEquals($zone, $germanyMember->getParentZone());
        $this->assertEquals($zoneRepository->get('de'), $germanyMember->getZone());

        $austriaMember = $members[1];
        $this->assertInstanceOf('CommerceGuys\Zone\Model\ZoneMemberCountry', $austriaMember);
        $this->assertEquals('3', $austriaMember->getId());
        $this->assertEquals('Austria', $austriaMember->getName());
        $this->assertEquals($zone, $austriaMember->getParentZone());
        $this->assertEquals('AT', $austriaMember->getCountryCode());
        $this->assertEquals('6691, 6991:6993', $austriaMember->getIncludedPostalCodes());
        $this->assertEquals('123456', $austriaMember->getExcludedPostalCodes());
        $this->assertEquals('CH-AG', $austriaMember->getAdministrativeArea());
        $this->assertEquals('CH-AG', $austriaMember->getLocality());
        $this->assertEquals('CH-AG', $austriaMember->getDependentLocality());

        // Test the static cache.
        $sameZone = $zoneRepository->get('de_vat');
        $this->assertSame($zone, $sameZone);
    }

    /**
     * @covers ::get
     * @covers ::loadDefinition
     * @covers ::createZoneFromDefinition
     * @expectedException \CommerceGuys\Zone\Exception\UnknownZoneException
     * @depends testConstructor
     */
    public function testGetNonExistingZone($zoneRepository)
    {
        $zone = $zoneRepository->get('rs');
    }

    /**
     * @covers ::getAll
     * @covers ::loadDefinition
     * @covers ::createZoneFromDefinition
     * @covers ::createZoneMemberCountryFromDefinition
     * @covers ::createZoneMemberZoneFromDefinition
     *
     * @uses \CommerceGuys\Zone\Repository\ZoneRepository::get
     * @uses \CommerceGuys\Zone\Model\Zone
     * @uses \CommerceGuys\Zone\Model\ZoneMember
     * @uses \CommerceGuys\Zone\Model\ZoneMemberCountry
     * @uses \CommerceGuys\Zone\Model\ZoneMemberZone
     * @uses \CommerceGuys\Zone\PostalCodeHelper
     * @depends testConstructor
     */
    public function testGetAll($zoneRepository)
    {
        $zones = $zoneRepository->getAll();
        $this->assertCount(2, $zones);
        $this->assertArrayHasKey('de', $zones);
        $this->assertArrayHasKey('de_vat', $zones);
        $this->assertEquals($zones['de']->getId(), 'de');
        $this->assertEquals($zones['de_vat']->getId(), 'de_vat');

        $zones = $zoneRepository->getAll('tax');
        $this->assertCount(1, $zones);
        $this->assertArrayHasKey('de_vat', $zones);
        $this->assertEquals($zones['de_vat']->getId(), 'de_vat');
    }
}
