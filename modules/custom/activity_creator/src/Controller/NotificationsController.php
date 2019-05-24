<?php

namespace Drupal\activity_creator\Controller;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
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
   */
  public function readNotificationCallback(): AjaxResponse {
    $account = \Drupal::currentUser();

    // Create AJAX Response object.
    // @todo: Implement a Ajax command instead and call via addCommand().
    $response = new AjaxResponse();
    $data = [
      'remaining_notifications' => $this->activities->markAllNotificationsAsSeen($account),
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
   *
   * @todo: Decide if we need to reload the view when button bashing. Could be
   *   beneficial as it's reloading the notifications sort of realtime.
   * @todo: Fix hide/show.
   * @todo: Mark as seen and also update the notification bell.
   */
  public function getNotificationListCallback(): AjaxResponse {
    // The data to display, will be the view.
    $view = Views::getView('activity_stream_notifications');
    $view->setDisplay('block_1');
    $rendered_view = $view->render();

    // Create a response.
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.js-notification-center-wrapper', $rendered_view));
    $response->addCommand(new InvokeCommand('.desktop.notification-bell.dropdown', 'addClass', array('open')));

    return $response;
  }

}
