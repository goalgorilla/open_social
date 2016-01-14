<?php

/**
 * @file
 * Contains \Drupal\devel_test\Controller\DevelSilentTestController.
 */

namespace Drupal\devel_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for devel module routes.
 */
class DevelSilentTestController extends ControllerBase {

  /**
   * Tests that devel is disabled if $GLOBALS['devel_shutdown'] is set.
   *
   * @return array
   *   A render array.
   */
  public function globalShoutdown() {
    $GLOBALS['devel_shutdown'] = FALSE;

    return [
      '#markup' => $this->t('$GLOBALS[\'devel_shutdown\'] = FALSE forces devel to be inactive.'),
    ];
  }

  /**
   * Tests that devel is disabled if response come from routes that are
   * declared with '_devel_silent' requirement.
   *
   * @return array
   *   A render array.
   */
  public function develSilentRouteRequirement() {
    return [
      '#markup' => $this->t('"_devel_silent" route requirement forces devel to be inactive.'),
    ];
  }

  /**
   * Tests that devel is disabled if is reyurned a JsonResponse response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response.
   */
  public function jsonResponse() {
    $data = ['data' => 'Devel is active only on HtmlResponse.'];
    return new JsonResponse($data);
  }

}
