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
   *
   * @return array
   *   Return render array.
   *
   * @see https://git.drupalcode.org/project/image_popup/-/blob/2.x/src/Controller/ImagePopup.php
   */
  public function render($fids, $pid) {
    $absolute_path = [];
    $fids = explode(',', $fids);

    foreach ($fids as $fid) {
      $file = $this->entityTypeManager()->getStorage('file')->load($fid);
      $image_uri = $file->getFileUri();

      // Get absolute path for original image.
      $absolute_path[] = Url::fromUri(file_create_url($image_uri))->getUri();
    }

    return [
      '#theme' => 'image_popup_details',
      '#url_popup' => $absolute_path,
      '#pid' => $pid,
    ];
  }

}
