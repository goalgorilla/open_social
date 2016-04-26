<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupTypeCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group type" caching.
 *
 * Cache context ID: 'group.type'.
 */
class GroupTypeCacheContext extends GroupCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group type');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return !empty($this->group) ? $this->group->bundle() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    if (!empty($this->group)) {
      // This needs to be invalidated whenever the group type is updated.
      $cacheable_metadata->setCacheTags($this->group->getGroupType()->getCacheTags());
    }

    return $cacheable_metadata;
  }

}
