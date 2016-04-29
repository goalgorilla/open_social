<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group" caching.
 *
 * Cache context ID: 'group'.
 */
class GroupCacheContext extends GroupCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->hasExistingGroup() ? $this->group->id() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // This needs to be invalidated whenever the group is updated. Note that new
    // groups can safely call ::getCacheTags, so there is no need to call
    // ::hasExistingGroup() here.
    if (!empty($this->group)) {
      $cacheable_metadata->setCacheTags($this->group->getCacheTags());
    }

    return $cacheable_metadata;
  }

}
