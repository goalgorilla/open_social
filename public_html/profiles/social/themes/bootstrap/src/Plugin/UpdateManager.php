<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\UpdateManager.
 */

namespace Drupal\bootstrap\Plugin;

use Drupal\bootstrap\Theme;

/**
 * Manages discovery and instantiation of Bootstrap updates.
 */
class UpdateManager extends PluginManager {

  /**
   * Constructs a new \Drupal\bootstrap\Plugin\UpdateManager object.
   *
   * @param \Drupal\bootstrap\Theme $theme
   *   The theme to use for discovery.
   */
  public function __construct(Theme $theme) {
    parent::__construct($theme, 'Plugin/Update', 'Drupal\bootstrap\Plugin\Update\UpdateInterface', 'Drupal\bootstrap\Annotation\BootstrapUpdate');
    $this->setCacheBackend(\Drupal::cache('discovery'), 'theme:' . $theme->getName() . ':update', $this->getCacheTags());
  }

  /**
   * Retrieves the latest update version.
   *
   * @return int
   *   The latest update version.
   */
  public function getLatestVersion() {
    $version = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
    if ($versions = $this->getVersions()) {
      $version = max(max($versions), $version);
    }
    return $version;
  }

  /**
   * Retrieves any pending updates.
   *
   * @return \Drupal\bootstrap\Plugin\Update\UpdateInterface[]
   *   An associative array containing update objects, keyed by their version.
   */
  public function getPendingUpdates() {
    $pending = [];
    $installed = $this->theme->getSetting('schema');
    foreach ($this->getUpdates() as $version => $update) {
      if ($version > $installed) {
        $pending[$version] = $update;
      }
    }
    return $pending;
  }

  /**
   * Retrieves update plugins for the theme.
   *
   * @return \Drupal\bootstrap\Plugin\Update\UpdateInterface[]
   *   An associative array containing update objects, keyed by their version.
   */
  public function getUpdates() {
    $updates = [];
    foreach ($this->getVersions() as $version) {
      $updates[$version] = $this->createInstance($version, ['theme' => $this->theme]);
    }
    ksort($updates, SORT_NUMERIC);
    return $updates;
  }

  /**
   * Retrieves the update schema versions for the theme.
   *
   * @return array
   *   An indexed array of schema versions.
   */
  protected function getVersions() {
    return array_keys($this->getDefinitions());
  }


}
