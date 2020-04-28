<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Activity content plugins.
 */
interface ActivityContextInterface extends PluginInspectionInterface {

  /**
   * Returns a batched list of recipients for this context.
   *
   * @param array $data
   *   The data.
   * @param int $last_id
   *   The last ID.
   * @param int $limit
   *   The limit.
   *
   * @return array
   *   An associative array of recipients, containing the following key-value
   *   pairs:
   *   - target_type: The entity type ID.
   *   - target_id: The entity ID.
   */
  public function getRecipients(array $data, $last_id, $limit);

  /**
   * Determines if the entity is valid for this context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if it's valid entity.
   */
  public function isValidEntity(EntityInterface $entity);

}
