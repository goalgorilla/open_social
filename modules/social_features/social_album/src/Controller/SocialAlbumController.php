<?php

namespace Drupal\social_album\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

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
  public function checkAccess(NodeInterface $node) {
    return AccessResult::allowedIf(
      $node->bundle() === 'album' &&
      !views_get_view_result('albums', 'embed_album_cover')
    );
  }

}
