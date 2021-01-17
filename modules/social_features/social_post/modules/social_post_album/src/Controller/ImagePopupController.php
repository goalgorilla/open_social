<?php

namespace Drupal\social_post_album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\social_post\Entity\PostInterface;

/**
 * Returns responses for Post Album routes.
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
    $items = [FALSE => [], TRUE => []];
    $found = FALSE;

    /** @var \Drupal\file\FileStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('file');

    // Show images in the correct order.
    foreach ($post->field_post_image->getValue() as $file) {
      if (!$found && $file['target_id'] == $first_fid) {
        $found = TRUE;
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = $storage->load($file['target_id']);

      $items[$found][] = Url::fromUri(file_create_url($file->getFileUri()))->setAbsolute()->toString();
    }

    return [
      '#theme' => 'album_post_popup',
      '#urls' => array_merge($items[TRUE], $items[FALSE]),
      '#pid' => $post->id(),
    ];
  }

}
