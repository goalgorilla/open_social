<?php

/**
 * @file
 * Contains \Drupal\address\Repository\ZoneRepository.
 */

namespace Drupal\address\Repository;

use CommerceGuys\Zone\Repository\ZoneRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines the zone repository.
 *
 * Zones are stored as config entities.
 */
class ZoneRepository implements ZoneRepositoryInterface {

  /**
   * The zone storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $zoneStorage;

  /**
   * Creates an ZoneRepository instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->zoneStorage = $entity_manager->getStorage('zone');
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    return $this->zoneStorage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($scope = NULL) {
    if ($scope) {
      $zones = $this->zoneStorage->loadByProperties(['scope' => $scope]);
    }
    else {
      $zones = $this->zoneStorage->loadMultiple();
    }

    return $zones;
  }

}
