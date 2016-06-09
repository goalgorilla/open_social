<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextBase.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Activity context plugin plugins.
 */
abstract class ActivityContextBase extends PluginBase implements ActivityContextInterface {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_id, $limit) {
    //TODO: Add basic implementation for the recipients based on the data given in the queue worker.

    return array();
  }

}
