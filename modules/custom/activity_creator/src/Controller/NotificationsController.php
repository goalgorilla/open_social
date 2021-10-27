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

    // Create a response.
    $response = new AjaxResponse();
    // Attach the view before marking the notification as seen.
    // This makes sure to render the correct background color of notifications.
    // @see social/modules/custom/activity_creator/activity.page.inc L#117
    $response->addCommand(new HtmlCommand('.js-notification-center-wrapper', $rendered_view));

    // Update the notification count to mark as seen.
    $notification_count = $this->activities->markAllNotificationsAsSeen($this->currentUser()) ? 0 : count($this->activities->getNotifications($this->currentUser()));
    $response->addCommand(new HtmlCommand('.notification-bell .badge', $notification_count));

    return $response;
  }

}
