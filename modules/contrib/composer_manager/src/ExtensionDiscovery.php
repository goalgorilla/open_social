<?php

/**
 * @file
 * Contains \Drupal\composer_manager\ExtensionDiscovery.
 */

namespace Drupal\composer_manager;

use Drupal\Core\Extension\ExtensionDiscovery as BaseExtensionDiscovery;

/**
 * Discovers available extensions in the filesystem.
 */
class ExtensionDiscovery extends BaseExtensionDiscovery {

  /**
   * Overrides BaseExtensionDiscovery::scan().
   *
   * Compared to the parent method:
   * - doesn't scan core/ because composer_manager doesn't need to care about
   *   core extensions (core already ships with their dependencies).
   * - scans all sites (to accommodate the poor souls still using multisite).
   */
  public function scan($type, $include_tests = NULL) {
    $searchdirs[static::ORIGIN_SITES_ALL] = 'sites/all';
    $searchdirs[static::ORIGIN_ROOT] = '';
    // Add all site directories, so that in a multisite environment each site
    // gets the necessary dependencies.
    foreach ($this->getSiteDirectories() as $index => $siteDirectory) {
      // The indexes are used as weights, so start at 10 to avoid conflicting
      // with the ones defined in the constants (ORIGIN_CORE, etc).
      $index = 10 + $index;
      $searchdirs[$index] = 'sites/' . $siteDirectory;
    }

    // We don't care about tests.
    $include_tests = FALSE;

    // From this point on the method is the same as the parent.
    $files = [];
    foreach ($searchdirs as $dir) {
      // Discover all extensions in the directory, unless we did already.
      if (!isset(static::$files[$dir][$include_tests])) {
        static::$files[$dir][$include_tests] = $this->scanDirectory($dir, $include_tests);
      }
      // Only return extensions of the requested type.
      if (isset(static::$files[$dir][$include_tests][$type])) {
        $files += static::$files[$dir][$include_tests][$type];
      }
    }

    // If applicable, filter out extensions that do not belong to the current
    // installation profiles.
    $files = $this->filterByProfileDirectories($files);
    // Sort the discovered extensions by their originating directories.
    $origin_weights = array_flip($searchdirs);
    $files = $this->sort($files, $origin_weights);

    // Process and return the list of extensions keyed by extension name.
    return $this->process($files);
  }

  /**
   * Resets the internal static cache.
   *
   * Used by unit tests to ensure a clean slate.
   */
  public function resetCache() {
    static::$files = [];
  }

  /**
   * Returns an array of all site directories.
   *
   * @return array
   *   An array of site directories. For example: ['default', 'test.site.com'].
   *   Doesn't include the 'all' directory since it doesn't represent a site.
   */
  protected function getSiteDirectories() {
    $site_directories = scandir($this->root . '/sites');
    $site_directories = array_filter($site_directories, function ($site_directory) {
      $is_directory = is_dir($this->root . '/sites/' . $site_directory);
      $not_hidden = substr($site_directory, 0, 1) != '.';
      $not_all = $site_directory != 'all';

      return $is_directory && $not_hidden && $not_all;
    });

    return $site_directories;
  }

}
