<?php

namespace Drupal\activity_creator\Controller;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
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
   * The current user services.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * NotificationsController constructor.
   *
   * @param \Drupal\activity_creator\ActivityNotifications $notifications
   *   The activity notifications.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account services.
   */
  public function __construct(ActivityNotifications $notifications, AccountInterface $account) {
    $this->activities = $notifications;
    $this->user = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('activity_creator.activity_notifications'),
      $container->get('current_user')
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
      'remaining_notifications' => $this->activities->markAllNotificationsAsSeen($this->user),
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getNotificationListCallback(): AjaxResponse {
    // The data to display, will be the view.
    $view = Views::getView('activity_stream_notifications');
    $view->setDisplay('block_1');
    $rendered_view = $view->render();

    $notification_count = (string) $this->activities->markAllNotificationsAsSeen($this->user);

    // Create a response.
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.js-notification-center-wrapper', $rendered_view));
    // Update the notification count to mark as read.
    $response->addCommand(new HtmlCommand('.notification-bell .badge', $notification_count));

    return $response;
  }

}
