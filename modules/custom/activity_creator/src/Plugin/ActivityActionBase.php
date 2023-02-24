<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\activity_logger\Entity\NotificationConfigEntityInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for Activity action plugins.
 */
abstract class ActivityActionBase extends PluginBase implements ActivityActionInterface {

  /**
   * {@inheritdoc}
   */
  public function create($entity) {
    if ($this->isValidEntity($entity)) {
      $this->createMessage($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createMessage($entity) {
    // Use the queue logger.
    $activity_logger_factory = \Drupal::service('activity_logger.activity_factory');
    // Create messages for all other types of content.
    $activity_logger_factory->createMessages($entity, $this->pluginId);
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    // Turn off this feature for all non-content entities.
    // Or non notification config entity.
    if ($entity instanceof ContentEntityInterface || $entity instanceof NotificationConfigEntityInterface) {
      return TRUE;
    }
    return FALSE;
  }

}
