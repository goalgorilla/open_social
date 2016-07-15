<?php

namespace Drupal\address\Repository;

use CommerceGuys\Zone\Repository\ZoneRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->zoneStorage = $entity_type_manager->getStorage('zone');
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
