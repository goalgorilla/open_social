<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Entity\Action;

/**
 * Defines an interface for Activity action plugins.
 */
interface ActivityActionInterface extends PluginInspectionInterface {

  /**
   * Creates a new message on the action with some logic behind it.
   */
  public function create(EntityInterface $entity): void;

  /**
   * Dumb function that can be called to create the message.
   */
  public function createMessage(EntityInterface $entity): void;

  /**
   * Checks if this is a valid entity for the action.
   */
  public function isValidEntity(EntityInterface $entity): bool;

}
