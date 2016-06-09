<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextInterface.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Activity content plugins.
 */
interface ActivityContextInterface extends PluginInspectionInterface {


  /**
   * Returns a batched list of recipients for this context.
   *
   * Format?
   *  array (
   *   id = uid or gip
   *   type = "user / group"
   * )
   */
  public function getRecipients(array $data, $last_id, $limit);


}
