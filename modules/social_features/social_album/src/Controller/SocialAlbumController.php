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
   * Provides a page with a form for deleting image from post and post view.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   *
   * @return array
   *   The renderable array.
   */
  public function deleteImage(NodeInterface $node, PostInterface $post, $fid) {
    return [
      'form' => $this->entityFormBuilder()->getForm($post, 'delete_image', ['fid' => $fid]),
      'view' => $this->entityTypeManager()->getViewBuilder('post')->view($post, 'featured'),
    ];
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
    return AccessResult::allowedIf(
      $node->bundle() === 'album' &&
      $node->getOwnerId() === $this->currentUser()->id()
    );
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

  /**
   * Checks access to the albums page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAlbumsAccess() {
    $status = $this->config('social_album.settings')->get('status');
    return AccessResult::allowedIf(!empty($status));
  }

}
