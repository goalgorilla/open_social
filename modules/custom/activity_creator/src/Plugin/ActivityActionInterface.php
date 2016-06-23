<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityActionInterface.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity action plugins.
 */
interface ActivityActionInterface extends PluginInspectionInterface {

  /**
   * Creates a new message on the action with some logic behind it.
   */
  public function create($entity);

  /**
   * Dumb function that can be called to create the message.
   */
  public function createMessage($entity);

  /**
   * Checks if this is a valid entity for the action.
   */
  public function isValidEntity($entity);



}
