<?php

namespace Drupal\activity_basics\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

/**
 * Provides an activity condition to check whether a node is published or not.
 *
 * @ActivityEntityCondition(
 *  id = "node_published",
 *  label = @Translation("Entity Published"),
 *  entities = {"node" = {}}
 * )
 */
class NodePublishedActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() == 'node' && $entity->isPublished()) {
      return TRUE;
    }
    return FALSE;
  }

}
