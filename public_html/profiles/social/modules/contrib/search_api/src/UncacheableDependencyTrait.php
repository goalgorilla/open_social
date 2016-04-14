<?php

namespace Drupal\search_api;

/**
 * Trait for objects that cannot be cached.
 *
 * @see \Drupal\Core\Cache\CacheableDependencyInterface
 *
 * @todo Remove once the same trait is in Core.
 */
trait UncacheableDependencyTrait {

  /**
   * The cache contexts associated with this object.
   *
   * These identify a specific variation/representation of the object.
   *
   * Cache contexts are tokens: placeholders that are converted to cache keys by
   * the @cache_contexts_manager service. The replacement value depends on the
   * request context (the current URL, language, and so on). They're converted
   * before storing an object in cache.
   *
   * @return string[]
   *   An array of cache context tokens, used to generate a cache ID.
   *
   * @see \Drupal\Core\Cache\Context\CacheContextsManager::convertTokensToKeys()
   * @see \Drupal\Core\Cache\CacheableDependencyInterface::getCacheContexts()
   */
  public function getCacheContexts() {
    return array();
  }

  /**
   * The cache tags associated with this object.
   *
   * When this object is modified, these cache tags will be invalidated.
   *
   * @return string[]
   *   A set of cache tags.
   *
   * @see \Drupal\Core\Cache\CacheableDependencyInterface::getCacheTags()
   */
  public function getCacheTags() {
    return array();
  }

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   *
   * @see \Drupal\Core\Cache\CacheableDependencyInterface::getCacheMaxAge()
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
