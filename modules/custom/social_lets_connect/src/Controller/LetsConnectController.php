<?php

namespace Drupal\social_lets_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class LetsConnectController.
 */
class LetsConnectController extends ControllerBase {

  /**
   * Main.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Return Redirect to getopensocial.com.
   */
  public function main() {
    return new TrustedRedirectResponse('https://www.getopensocial.com');
  }

}
