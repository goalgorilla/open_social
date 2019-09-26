<?php

namespace Drupal\activity_send_push_notification;

/**
 * Interface PushInterface.
 *
 * @package Drupal\activity_send_push_notification
 */
interface PushInterface {

  /**
   * Build form elements.
   *
   * @return array
   *   The form elements.
   */
  public function form();

}
