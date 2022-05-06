<?php

namespace Drupal\social_post_album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\social_post\Entity\PostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Post Album routes.
 *
 * @package Drupal\social_post_album\Controller
 */
class ImagePopupController extends ControllerBase {

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * Construct a ImagePopupController object.
   *
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file url generator service.
   */
  public function __construct(FileUrlGenerator $file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container object.
   *
   * @return \Drupal\social_post_album\Controller\ImagePopupController|static
   *   Instance of the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator')
    );
  }

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

      $items[$found][] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }

    return [
      '#theme' => 'album_post_popup',
      '#urls' => array_merge($items[TRUE], $items[FALSE]),
      '#pid' => $post->id(),
    ];
  }

}
