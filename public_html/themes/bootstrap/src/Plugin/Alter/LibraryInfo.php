<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Alter\LibraryInfo.
 */

namespace Drupal\bootstrap\Plugin\Alter;

use Drupal\bootstrap\Annotation\BootstrapAlter;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Implements hook_library_info_alter().
 *
 * @BootstrapAlter("library_info")
 */
class LibraryInfo extends PluginBase implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$libraries, &$extension = NULL, &$context2 = NULL) {
    if ($extension === 'bootstrap') {
      // Retrieve the theme's CDN provider and assets.
      $provider = $this->theme->getProvider();
      $assets = $provider ? $provider->getAssets() : [];

      // Immediately return if there is no provider or assets.
      if (!$provider || !$assets) {
        return;
      }

      // Merge the assets into the library info.
      $libraries['base-theme'] = NestedArray::mergeDeepArray([$assets, $libraries['base-theme']], TRUE);

      // Add a specific version and theme CSS overrides file.
      // @todo This should be retrieved by the Provider API.
      $version = $this->theme->getSetting('cdn_' . $provider->getPluginId() . '_version') ?: Bootstrap::FRAMEWORK_VERSION;
      $libraries['base-theme']['version'] = $version;
      $provider_theme = $this->theme->getSetting('cdn_' . $provider->getPluginId() . '_theme') ?: 'bootstrap';
      $provider_theme = $provider_theme === 'bootstrap' || $provider_theme === 'bootstrap_theme' ? '' : "-$provider_theme";

      foreach ($this->theme->getAncestry(TRUE) as $ancestor) {
        $overrides = $ancestor->getPath() . "/css/$version/overrides$provider_theme.min.css";
        if (file_exists($overrides)) {
          // Since this uses a relative path to the ancestor from DRUPAL_ROOT,
          // we must prefix the entire path with / so it doesn't append the
          // active theme's path (which would duplicate the prefix).
          $libraries['base-theme']['css']['theme']["/$overrides"] = [];
          break;
        }
      }
    }
    // Core replacements.
    elseif ($extension === 'core') {
      // Replace core dialog/jQuery UI implementations with Bootstrap Modals.
      if ($this->theme->getSetting('modal_enabled')) {
        $libraries['drupal.dialog']['override'] = 'bootstrap/drupal.dialog';
        $libraries['drupal.dialog.ajax']['override'] = 'bootstrap/drupal.dialog.ajax';
      }
    }
  }

}
