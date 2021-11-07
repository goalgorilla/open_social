<?php

namespace Drupal\social_lets_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controller class for social_lets_connect.
 */
class LetsConnectController extends ControllerBase {

  /**
   * Main.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Return Redirect to getopensocial.com.
   */
  public function main(): TrustedRedirectResponse {
    return new TrustedRedirectResponse('https://www.getopensocial.com');
  }

}
