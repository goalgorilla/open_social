<?php

namespace Drupal\social_lets_connect_usage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class UsageDataController.
 */
class UsageDataController extends ControllerBase {

  /**
   * Usage data settings.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Return Usage data settings form.
   */
  public function main() {
    return new TrustedRedirectResponse('https://www.getopensocial.com');
  }

}
