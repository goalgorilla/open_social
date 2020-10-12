<?php

namespace Drupal\social_post_album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class ImagePopupController.
 *
 * @package Drupal\social_post_album\Controller
 */
class ImagePopupController extends ControllerBase {

  /**
   * Render images and post in pop-up.
   *
   * @param string $fids
   *   Ids of images.
   * @param string $pid
   *   The post ID.
   * @param string $first_fid
   *   The first file ID.
   *
   * @return array
   *   Return render array.
   *
   * @see https://git.drupalcode.org/project/image_popup/-/blob/2.x/src/Controller/ImagePopup.php
   */
  public function render($fids, $pid, $first_fid) {
    $absolute_path = [];
    $fids = explode(',', $fids);

    foreach ($fids as $fid) {
      if ($fid == $first_fid) {
        continue;
      }
      $file = $this->entityTypeManager()->getStorage('file')->load($fid);
      $image_uri = $file->getFileUri();

      // Get absolute path for original image.
      $absolute_path[] = Url::fromUri(file_create_url($image_uri))->getUri();
    }

    $first_file = $this->entityTypeManager()->getStorage('file')->load($first_fid);
    $first_image_uri = $first_file->getFileUri();
    array_unshift($absolute_path, Url::fromUri(file_create_url($first_image_uri))->getUri());

    return [
      '#theme' => 'image_popup_details',
      '#url_popup' => $absolute_path,
      '#pid' => $pid,
    ];
  }

}
