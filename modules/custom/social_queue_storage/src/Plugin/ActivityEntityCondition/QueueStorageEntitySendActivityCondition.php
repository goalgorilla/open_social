<?php

namespace Drupal\social_queue_storage\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

/**
 * Provides a 'QueueStorageEntitySendActivityAction` activity action.
 *
 * @ActivityEntityCondition(
 *   id = "queue_store_entity_send_activity_condition",
 *   label = @Translation("Queue Store Entity has is finished status."),
 *   entities = {"queue_storage_entity" = {}}
 * )
 */
class QueueStorageEntitySendActivityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $entity */
    if ($entity->getEntityTypeId() === 'queue_storage_entity') {
      return $entity->isFinished();
    }

    return FALSE;
  }

}
