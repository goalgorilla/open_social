<?php

namespace Drupal\activity_send_push_notification;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface PushInterface.
 *
 * @package Drupal\activity_send_push_notification
 */
interface PushInterface extends ContainerFactoryPluginInterface {

  /**
   * Build form elements.
   *
   * @return array
   *   The form elements.
   */
  public function form();

}
