<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityAction\CreateActivityAction.
 */

namespace Drupal\activity_basics\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides a 'CreateActivityAction' acitivy action.
 *
 * @ActivityAction(
 *  id = "create_entitiy_action",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class CreateActivityAction extends ActivityActionBase {

  /**
   * @inheritdoc
   */
  public function create($entity) {

    if ($this->isValidEntity($entity)) {

      // For nodes we make an exception, since they are potentially placed in
      // groups, which we cannot know here yet.
      if ($entity instanceof \Drupal\node\Entity\Node) {
        $data['entity_id'] = $entity->id();
        $queue = \Drupal::queue('activity_logger_message');
        $queue->createItem($data);
      }
      else {
        $this->createMessage($entity);
      }
    }
  }


}
