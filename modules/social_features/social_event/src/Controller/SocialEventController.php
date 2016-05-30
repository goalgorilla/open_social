<?php

/**
 * @file
 * Contains \Drupal\social_event\Controller\SocialEventController.
 */

namespace Drupal\social_event\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SocialEventController.
 *
 * @package Drupal\social_event\Controller
 */
class SocialEventController extends ControllerBase {

  /**
   * Redirectmyevents.
   *
   * Redirect to a users events.
   */
  public function redirectMyEvents() {
    return $this->redirect('view.events.events_overview', array('user' => $this->currentUser()->id()));
  }
}
