<?php

namespace Drupal\activity_send_push_notification;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class PushBase.
 *
 * @package Drupal\activity_send_push_notification
 */
abstract class PushBase extends PluginBase implements PushInterface {

  /**
   * {@inheritdoc}
   */
  public function form() {
    return [];
  }

}
