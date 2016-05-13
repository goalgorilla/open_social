<?php

/**
 * @file
 * Contains \Drupal\features\Controller\FeaturesController.
 */

namespace Drupal\features\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for config module routes.
 */
class FeaturesController implements ContainerInjectionInterface {

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      new FileDownloadController(),
      $container->get('csrf_token')
    );
  }

  /**
   * Constructs a FeaturesController object.
   *
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(FileDownloadController $file_download_controller, CsrfTokenGenerator $csrf_token) {
    $this->fileDownloadController = $file_download_controller;
    $this->csrfToken = $csrf_token;
  }

  /**
   * Downloads a tarball of the site configuration.
   *
   * @param string $uri
   *   The URI to download.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The downloaded file.
   */
  public function downloadExport($uri, Request $request) {
    if ($uri) {
      // @todo Simplify once https://www.drupal.org/node/2630920 is solved.
      if (!$this->csrfToken->validate($request->query->get('token'), $uri)) {
        throw new AccessDeniedHttpException();
      }

      $request = new Request(array('file' => $uri));
      return $this->fileDownloadController->download($request, 'temporary');
    }
  }

}
