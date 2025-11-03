<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Drupal\Core\Cache\Cache;
use Drupal\redis\Cache\CacheBase;

/**
 * Clears the Redis cache when a test database is loaded.
 */
class RedisContext implements Context {

  /**
   * Ensure the Redis cache is cleared.
   *
   * The Redis module doesn't clear any existing Redis caches when the module is
   * enabled. To avoid problems that may be caused by this, we clear all Redis
   * caches when a database is loaded.
   *
   * See https://www.drupal.org/project/redis/issues/3400450.
   */
  public function onDatabaseLoaded() : void {
    // If the redis module isn't enabled, there's nothing for us to do.
    if (!\Drupal::hasService('cache.backend.redis')) {
      return;
    }

    $cache_backend_factory = \Drupal::service('cache.backend.redis');
    // Delete all cache bins backed by Redis when installing since processes
    // like Drush might otherwise get confused. This needs to happen regardless
    // of whether we install from scratch or from config.
    foreach (Cache::getBins() as $id => $bin) {
      // During install, the bins will not yet be configured to use Redis, so
      // for cleanup we treat every bin as if it's in Redis.
      if (!$bin instanceof CacheBase) {
        $bin = $cache_backend_factory->get($id);
      }

      $bin->deleteAll();
    }
  }

}
