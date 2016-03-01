<?php

namespace CommerceGuys\Zone\Matcher;

use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Zone\Repository\ZoneRepositoryInterface;

class ZoneMatcher implements ZoneMatcherInterface
{
    /**
     * Zone repository.
     *
     * @var ZoneRepositoryInterface
     */
    protected $repository;

    /**
     * Creates a ZoneMatcher instance.
     *
     * @param ZoneRepositoryInterface $repository
     */
    public function __construct(ZoneRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function match(AddressInterface $address, $scope = null)
    {
        $zones = $this->matchAll($address, $scope);

        return count($zones) ? $zones[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function matchAll(AddressInterface $address, $scope = null)
    {
        // Find all matching zones.
        $results = [];
        foreach ($this->repository->getAll($scope) as $zone) {
            if ($zone->match($address)) {
                $results[] = [
                    'priority' => (int) $zone->getPriority(),
                    'zone' => $zone,
                ];
            }
        }
        // Sort the matched zones by priority.
        usort($results, function ($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return ($a['priority'] > $b['priority']) ? -1 : 1;
        });
        // Create the final zone array from the results.
        $zones = [];
        foreach ($results as $result) {
            $zones[] = $result['zone'];
        }

        return $zones;
    }
}
