<?php

namespace Drupal\social_kpi_lite\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for social_kpi_lite module routes.
 */
class SocialKPILiteController extends ControllerBase {

  /**
   * Empty page for the kpi analytics.
   */
  public function analytics(): array {
    return [
      '#markup' => '',
    ];
  }

}
