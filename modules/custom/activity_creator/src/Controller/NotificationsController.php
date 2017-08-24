<?php

namespace Drupal\activity_creator\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Notifications controller.
 */
class NotificationsController extends ControllerBase {

  /**
   * Ajax callback to mark notifications as read.
   */
  public function readNotificationCallback() {
    $account = \Drupal::currentUser();

    // @todo: Add dependency injection.
    $activity_notifications = \Drupal::service('activity_creator.activity_notifications');
    $remaining_notifications = $activity_notifications->markAllNotificationsAsSeen($account);

    // Create AJAX Response object.
    // @todo: Implement a Ajax command instead and call via addCommand().
    $response = new AjaxResponse();
    $data = [
      'remaining_notifications' => $remaining_notifications,
    ];
    $response->setData($data);

    // Return ajax response.
    return $response;
  }

}
