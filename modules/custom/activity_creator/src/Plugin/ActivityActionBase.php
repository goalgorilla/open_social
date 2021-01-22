<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;

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
  public function isValidEntity($entity) {
    // Turn off this feature for all non-content entities.
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

}
