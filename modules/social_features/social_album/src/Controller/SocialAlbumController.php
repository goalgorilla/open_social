<?php

namespace Drupal\social_album\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;

/**
 * Class SocialAlbumController.
 *
 * @package Drupal\social_album\Controller
 */
class SocialAlbumController extends ControllerBase {

  /**
   * Provides a generic title callback for the first post of the album.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to page of the post.
   */
  public function title(NodeInterface $node) {
    return $this->t('Add images to album @name', ['@name' => $node->label()]);
  }

  /**
   * Checks access to the form of a post which will be linked to the album.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAlbumAccess(NodeInterface $node) {
    return AccessResult::allowedIf($node->bundle() === 'album');
  }

  /**
   * Checks access to the page for deleting the image from the post.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkImageAccess(NodeInterface $node, PostInterface $post, $fid) {
    if (
      $this->checkAlbumAccess($node)->isAllowed() &&
      $post->bundle() === 'photo' &&
      !$post->field_album->isEmpty() &&
      $post->field_album->target_id === $node->id() &&
      !$post->field_post_image->isEmpty()
    ) {
      foreach ($post->field_post_image->getValue() as $item) {
        if ($item['target_id'] === $fid) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

}
