<?php

namespace Drupal\social_post_album\Service;

use Drupal\Core\Render\ElementInfoManager;

/**
 * Provides an extended plugin manager for element plugins.
 */
class SocialPostAlbumElementInfoManager extends ElementInfoManager {

  /**
   * {@inheritdoc}
   */
  public function getInfo($type) {
    if ($type === 'managed_file') {
      $type = 'social_post_album_managed_file';
    }

    return parent::getInfo($type);
  }

}
