<?php

/**
 * @file
 * Contains \Drupal\features\Controller\FeaturesController.
 */

namespace Drupal\features\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      new FileDownloadController()
    );
  }

  /**
   * Constructs a FeaturesController object.
   *
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(FileDownloadController $file_download_controller) {
    $this->fileDownloadController = $file_download_controller;
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    $session = \Drupal::request()->getSession();
    if (isset($session)) {
      $archive_name = $session->get('features_download');
      $request = new Request(array('file' => $archive_name));
      return $this->fileDownloadController->download($request, 'temporary');
    }
  }

}
