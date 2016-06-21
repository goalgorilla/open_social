<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for social_core module routes.
 */
class SocialCoreController extends ControllerBase {

  /**
   *
   */
  public function accessDenied() {
    // Get the front page URL.
    $frontpage = $this->config('system.site')->get('page.front');

    // Determine the message we want to set.
    $text = $this->t("<p>You have insufficient permissions to view the page you're trying to access. There could be several reasons for this:</p><ul><li>You are trying to edit content you're not allowed to edit.</li><li>You are trying to view content (from a group) you don't have access to.</li><li>You are trying to access administration pages.</li></ul><p>Click the back button of your browser to go back where you came from or click <a href=\":url\">here</a> to go to the homepage</p>", array(':url' => $frontpage));

    // Return the message in the render array.
    return array('#markup' => $text);
  }

}
