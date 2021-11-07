<?php

namespace Drupal\activity_basics\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;
use Drupal\node\NodeInterface;

/**
 * Provides a 'CreateActivityAction' activity action.
 *
 * @ActivityAction(
 *  id = "create_entitiy_action",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class CreateActivityAction extends ActivityActionBase {

  /**
   * {@inheritdoc}
   */
  public function create($entity): void {

    if ($this->isValidEntity($entity)) {

      // For nodes we make an exception, since they are potentially placed in
      // groups, which we cannot know here yet.
      if ($entity instanceof NodeInterface) {
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
