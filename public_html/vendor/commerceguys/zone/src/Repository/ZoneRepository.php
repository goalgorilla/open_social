<?php

namespace CommerceGuys\Zone\Repository;

use CommerceGuys\Zone\Exception\UnknownZoneException;
use CommerceGuys\Zone\Model\Zone;
use CommerceGuys\Zone\Model\ZoneMemberCountry;
use CommerceGuys\Zone\Model\ZoneMemberZone;

/**
 * Manages zones based on JSON definitions.
 */
class ZoneRepository implements ZoneRepositoryInterface
{
    /**
     * The path where zone definitions are stored.
     *
     * @var string
     */
    protected $definitionPath;

    /**
     * Zone index.
     *
     * @var array
     */
    protected $zoneIndex = [];

    /**
     * Zones.
     *
     * @var array
     */
    protected $zones = [];

    /**
     * Creates a ZoneRepository instance.
     *
     * @param string $definitionPath Path to the zone definitions.
     */
    public function __construct($definitionPath)
    {
        $this->definitionPath = $definitionPath;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!isset($this->zones[$id])) {
            $definition = $this->loadDefinition($id);
            $this->zones[$id] = $this->createZoneFromDefinition($definition);
        }

        return $this->zones[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($scope = null)
    {
        // Build the list of all available zones.
        if (empty($this->zoneIndex)) {
            if ($handle = opendir($this->definitionPath)) {
                while (false !== ($entry = readdir($handle))) {
                    if (substr($entry, 0, 1) != '.') {
                        $id = strtok($entry, '.');
                        $this->zoneIndex[] = $id;
                    }
                }
                closedir($handle);
            }
        }

        // Load each zone, filter by scope if needed.
        $zones = [];
        foreach ($this->zoneIndex as $id) {
            $zone = $this->get($id);
            if (is_null($scope) || ($zone->getScope() == $scope)) {
                $zones[$id] = $this->get($id);
            }
        }

        return $zones;
    }

    /**
     * Loads the zone definition for the provided id.
     *
     * @param string $id The zone id.
     *
     * @return array The zone definition.
     */
    protected function loadDefinition($id)
    {
        $filename = $this->definitionPath . $id . '.json';
        $definition = @file_get_contents($filename);
        if (empty($definition)) {
            throw new UnknownZoneException($id);
        }
        $definition = json_decode($definition, true);
        $definition['id'] = $id;

        return $definition;
    }

    /**
     * Creates a Zone instance from the provided definition.
     *
     * @param array $definition The zone definition.
     *
     * @return Zone
     */
    protected function createZoneFromDefinition(array $definition)
    {
        $zone = new Zone();
        // Bind the closure to the Zone object, giving it access to
        // its protected properties. Faster than both setters and reflection.
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
            $this->name = $definition['name'];
            if (isset($definition['scope'])) {
                $this->scope = $definition['scope'];
            }
            if (isset($definition['priority'])) {
                $this->priority = $definition['priority'];
            }
        }, $zone, '\CommerceGuys\Zone\Model\Zone');
        $setValues($definition);

        // Add the zone members.
        foreach ($definition['members'] as $memberDefinition) {
            if ($memberDefinition['type'] == 'country') {
                $zoneMember = $this->createZoneMemberCountryFromDefinition($memberDefinition);
                $zone->addMember($zoneMember);
            } elseif ($memberDefinition['type'] == 'zone') {
                $zoneMember = $this->createZoneMemberZoneFromDefinition($memberDefinition);
                $zone->addMember($zoneMember);
            }
        }

        return $zone;
    }

    /**
     * Creates a ZoneMemberCountry instance from the provided definition.
     *
     * @param array $definition The zone member definition.
     *
     * @return ZoneMemberCountry
     */
    protected function createZoneMemberCountryFromDefinition(array $definition)
    {
        $zoneMember = new ZoneMemberCountry();
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
            $this->name = $definition['name'];
            $this->countryCode = $definition['country_code'];
            if (isset($definition['administrative_area'])) {
                $this->administrativeArea = $definition['administrative_area'];
            }
            if (isset($definition['locality'])) {
                $this->locality = $definition['locality'];
            }
            if (isset($definition['dependent_locality'])) {
                $this->dependentLocality = $definition['dependent_locality'];
            }
            if (isset($definition['included_postal_codes'])) {
                $this->includedPostalCodes = $definition['included_postal_codes'];
            }
            if (isset($definition['excluded_postal_codes'])) {
                $this->excludedPostalCodes = $definition['excluded_postal_codes'];
            }
        }, $zoneMember, '\CommerceGuys\Zone\Model\ZoneMemberCountry');
        $setValues($definition);

        return $zoneMember;
    }

    /**
     * Creates a ZoneMemberZone instance from the provided definition.
     *
     * @param array $definition The zone member definition.
     *
     * @return ZoneMemberZone
     */
    protected function createZoneMemberZoneFromDefinition(array $definition)
    {
        $zone = $this->get($definition['zone']);
        $zoneMember = new ZoneMemberZone();
        $zoneMember->setZone($zone);
        $setValues = \Closure::bind(function ($definition) {
            $this->id = $definition['id'];
        }, $zoneMember, '\CommerceGuys\Zone\Model\ZoneMemberZone');
        $setValues($definition);

        return $zoneMember;
    }
}
