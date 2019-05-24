<?php

namespace Drupal\social_activity\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Class NotificationController.
 */
class NotificationController extends ControllerBase {

  /**
   * Get a view of notifications.
   */
  public function getNotificationsList(): AjaxResponse {
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
