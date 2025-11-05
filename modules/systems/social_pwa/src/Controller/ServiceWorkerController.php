<?php

namespace Drupal\social_pwa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ServiceWorkerController.
 *
 * @package Drupal\social_pwa\Controller
 */
class ServiceWorkerController extends ControllerBase {

  /**
   * Make the service worker look from the root.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return response.
   */
  public function serviceWorker() {
    $path = \Drupal::service('extension.list.module')->getPath('social_pwa');
    $sw = file_get_contents($path . '/js/sw.js');

    return new Response($sw, 200, [
      'Content-Type' => 'application/javascript',
      'Service-Worker-Allowed' => '/',
    ]);
  }

}
