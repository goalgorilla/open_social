<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity content plugins.
 */
interface ActivityContextInterface extends PluginInspectionInterface {

  /**
   * Returns a batched list of recipients for this context.
   *
   * Format
   * array(
   *   array (
   *     id = uid or gip
   *     type = "user / group"
   *   )
   * )
   */
  public function getRecipients(array $data, $last_id, $limit);

  /**
   * Determines if the entity is valid for this context.
   */
  public function isValidEntity($entity);

}
