<?php

namespace Drupal\activity_creator\Controller;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notifications controller.
 */
class NotificationsController extends ControllerBase {

  /**
   * The activity notification service.
   *
   * @var \Drupal\activity_creator\ActivityNotifications
   */
  protected $activities;

  /**
   * NotificationsController constructor.
   *
   * @param \Drupal\activity_creator\ActivityNotifications $notifications
   *   The activity notifications.
   */
  public function __construct(ActivityNotifications $notifications) {
    $this->activities = $notifications;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('activity_creator.activity_notifications')
    );
  }

  /**
   * Ajax callback to mark notifications as read.
   *
   * @deprecated in social:8.x-7.0 and is removed from social:8.x-8.0. Use NotificationsController::getNotificationListCallback() instead.
   * @see https://www.drupal.org/project/social/issues/3056821
   */
  public function readNotificationCallback(): AjaxResponse {
    // Create AJAX Response object.
    $response = new AjaxResponse();
    $data = [
      'remaining_notifications' => $this->activities->markAllNotificationsAsSeen($this->currentUser()),
    ];
    $response->setData($data);

    // Return ajax response.
    return $response;
  }

  /**
   * Get the notification list.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns a render array list of notifications.
   */
  public function getNotificationListCallback(): AjaxResponse {
    // The data to display, will be the view.
    $view = Views::getView('activity_stream_notifications');
    $view->setDisplay('block_1');
    $rendered_view = $view->render();

    // Set the notification count.
    if ($this->activities->markAllNotificationsAsSeen($this->currentUser())) {
      // All the notifications are marked as seen, set notification count to 0.
      $notification_count = 0;
    }
    else {
      // Else fetch and count received notifications by user.
      $notification_count = count($this->activities->getNotifications());
    }

    // Create a response.
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.js-notification-center-wrapper', $rendered_view));
    // Update the notification count to mark as read.
    $response->addCommand(new HtmlCommand('.notification-bell .badge', $notification_count ?? 0));

    return $response;
  }

}
