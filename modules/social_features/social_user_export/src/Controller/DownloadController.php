<?php

namespace Drupal\social_user_export\Controller;

use \Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\BinaryFileResponse;
use \Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Returns responses for social_user_export module routes.
 */
class DownloadController extends ControllerBase {

  /**
   * Returns headers to force download file.
   *
   * @param string $name
   *   The name of the file.
   *
   * @return BinaryFileResponse
   *   The file object.
   */
  public function download($name) {
    $file_path = file_directory_temp() . '/' . $name;
    $response = new BinaryFileResponse($file_path);
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $name
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
