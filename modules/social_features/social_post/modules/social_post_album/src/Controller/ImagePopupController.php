<?php

namespace Drupal\social_post_album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\social_post\Entity\PostInterface;

/**
 * Class ImagePopupController.
 *
 * @package Drupal\social_post_album\Controller
 */
class ImagePopupController extends ControllerBase {

  /**
   * Render images and post in pop-up.
   *
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity.
   * @param string $first_fid
   *   The first file ID.
   *
   * @return array
   *   Return render array.
   *
   * @see https://git.drupalcode.org/project/image_popup/-/blob/2.x/src/Controller/ImagePopup.php
   */
  public function render(PostInterface $post, $first_fid) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $post_image */
    $post_image = $post->field_post_image;
    $files = $post_image->getValue();

    $fids = [];
    $index = 0;
    // Show images in the correct order.
    foreach ($files as $file) {
      if ($file['target_id'] == $first_fid) {
        $index++;
      }
      $fids[$index][] = $file['target_id'];
    }
    [$before, $after] = $fids;
    $fids = array_merge($after, $before);
    if (!$before) {
      $fids = $after;
    }

    $urls = [];
    foreach ($fids as $fid) {
      $file = $this->entityTypeManager()->getStorage('file')->load($fid);
      $image_uri = $file->getFileUri();

      // Get absolute path for original image.
      $urls[] = Url::fromUri(file_create_url($image_uri))->getUri();
    }

    return [
      '#theme' => 'album_post_popup',
      '#urls' => $urls,
      '#pid' => $post->id(),
    ];
  }

}
