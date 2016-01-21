<?php
/**
 * @file
 * Contains \Drupal\bootstrap.
 */

namespace Drupal\bootstrap;

use Drupal\bootstrap\Plugin\ProviderManager;
use Drupal\bootstrap\Plugin\SettingManager;
use Drupal\bootstrap\Plugin\UpdateManager;
use Drupal\bootstrap\Utility\Crypt;
use Drupal\bootstrap\Utility\Storage;
use Drupal\bootstrap\Utility\StorageItem;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Defines a theme object.
 */
class Theme {

  /**
   * Ignores the following folders during file scans of a theme.
   *
   * @see \Drupal\bootstrap\Theme::IGNORE_ASSETS
   * @see \Drupal\bootstrap\Theme::IGNORE_CORE
   * @see \Drupal\bootstrap\Theme::IGNORE_DOCS
   * @see \Drupal\bootstrap\Theme::IGNORE_DEV
   */
  const IGNORE_DEFAULT = -1;

  /**
   * Ignores the folders "assets", "css", "images" and "js".
   */
  const IGNORE_ASSETS = 0x1;

  /**
   * Ignores the folders "config", "lib" and "src".
   */
  const IGNORE_CORE = 0x2;

  /**
   * Ignores the folders "docs" and "documentation".
   */
  const IGNORE_DOCS = 0x4;

  /**
   * Ignores "bower_components", "grunt", "node_modules" and "starterkits".
   */
  const IGNORE_DEV = 0x8;

  /**
   * Ignores the folders "templates" and "theme".
   */
  const IGNORE_TEMPLATES = 0x16;

  /**
   * The current theme info.
   *
   * @var array
   */
  protected $info;

  /**
   * The current theme Extension object.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $theme;

  /**
   * An array of installed themes.
   *
   * @var array
   */
  protected $themes;

  /**
   * Theme handler object.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Theme constructor.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   A theme \Drupal\Core\Extension\Extension object.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler object.
   */
  public function __construct(Extension $theme, ThemeHandlerInterface $theme_handler) {
    $name = $theme->getName();
    $this->theme = $theme;
    $this->themeHandler = $theme_handler;
    $this->themes = $this->themeHandler->listInfo();
    $this->info = isset($this->themes[$name]->info) ? $this->themes[$name]->info : [];

    // Only install the theme if there is no schema version currently set.
    if (!$this->getSetting('schema')) {
      $this->install();
    }
  }

  /**
   * Returns the theme machine name.
   *
   * @return string
   *   Theme machine name.
   */
  public function __toString() {
    return $this->getName();
  }

  /**
   * Retrieves the theme's settings array appropriate for drupalSettings.
   *
   * @return array
   *   The theme settings for drupalSettings.
   */
  public function drupalSettings() {
    $cache = $this->getCache('drupalSettings');
    $drupal_settings = $cache->getAll();
    if (!$drupal_settings) {
      foreach ($this->getSettingPlugins() as $name => $setting) {
        if ($setting->drupalSettings()) {
          $drupal_settings[$name] = TRUE;
        }
      }
      $cache->setMultiple($drupal_settings);
    }
    return array_intersect_key($this->settings()->get(), $drupal_settings);
  }

  /**
   * Wrapper for the core file_scan_directory() function.
   *
   * Finds all files that match a given mask in the given directories and then
   * caches the results. A general site cache clear will force new scans to be
   * initiated for already cached directories.
   *
   * @param string $mask
   *   The preg_match() regular expression of the files to find.
   * @param string $subdir
   *   Sub-directory in the theme to start the scan, without trailing slash. If
   *   not set, the base path of the current theme will be used.
   * @param array $options
   *   Options to pass, see file_scan_directory() for addition options:
   *   - ignore_flags: (int|FALSE) A bitmask to indicate which directories (if
   *     any) should be skipped during the scan. Must also not contain a
   *     "nomask" property in $options. Value can be any of the following:
   *     - \Drupal\bootstrap::IGNORE_CORE
   *     - \Drupal\bootstrap::IGNORE_ASSETS
   *     - \Drupal\bootstrap::IGNORE_DOCS
   *     - \Drupal\bootstrap::IGNORE_DEV
   *     - \Drupal\bootstrap::IGNORE_THEME
   *     Pass FALSE to iterate over all directories in $dir.
   *
   * @return array
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' members corresponding to the matching files.
   *
   * @see file_scan_directory()
   */
  public function fileScan($mask, $subdir = NULL, array $options = []) {
    $path = $this->getPath();

    // Append addition sub-directories to the path if they were provided.
    if (isset($subdir)) {
      $path .= '/' . $subdir;
    }

    // Default ignore flags.
    $options += [
      'ignore_flags' => self::IGNORE_DEFAULT,
    ];
    $flags = $options['ignore_flags'];
    if ($flags === self::IGNORE_DEFAULT) {
      $flags = self::IGNORE_CORE | self::IGNORE_ASSETS | self::IGNORE_DOCS | self::IGNORE_DEV;
    }

    // Save effort by skipping directories that are flagged.
    if (!isset($options['nomask']) && $flags) {
      $ignore_directories = [];
      if ($flags & self::IGNORE_ASSETS) {
        $ignore_directories += ['assets', 'css', 'images', 'js'];
      }
      if ($flags & self::IGNORE_CORE) {
        $ignore_directories += ['config', 'lib', 'src'];
      }
      if ($flags & self::IGNORE_DOCS) {
        $ignore_directories += ['docs', 'documentation'];
      }
      if ($flags & self::IGNORE_DEV) {
        $ignore_directories += ['bower_components', 'grunt', 'node_modules', 'starterkits'];
      }
      if ($flags & self::IGNORE_TEMPLATES) {
        $ignore_directories += ['templates', 'theme'];
      }
      if (!empty($ignore_directories)) {
        $options['nomask'] = '/^' . implode('|', $ignore_directories) . '$/';
      }
    }

    // Retrieve cache.
    $files = $this->getCache('files');

    // Generate a unique hash for all parameters passed as a change in any of
    // them could potentially return different results.
    $hash = Crypt::generateHash($mask, $path, $options);

    if (!$files->has($hash)) {
      $files->set($hash, file_scan_directory($path, $mask, $options));
    }
    return $files->get($hash, []);
  }

  /**
   * Retrieves the full base/sub-theme ancestry of a theme.
   *
   * @param bool $reverse
   *   Whether or not to return the array of themes in reverse order, where the
   *   active theme is the first entry.
   *
   * @return \Drupal\bootstrap\Theme[]
   *   An associative array of \Drupal\bootstrap objects (theme), keyed
   *   by machine name.
   */
  public function getAncestry($reverse = FALSE) {
    $ancestry = $this->themeHandler->getBaseThemes($this->themes, $this->getName());
    foreach (array_keys($ancestry) as $name) {
      $ancestry[$name] = Bootstrap::getTheme($name, $this->themeHandler);
    }
    $ancestry[$this->getName()] = $this;
    return $reverse ? array_reverse($ancestry) : $ancestry;
  }

  /**
   * Retrieves an individual item from a theme's cache in the database.
   *
   * @param string $name
   *   The name of the item to retrieve from the theme cache.
   * @param mixed $default
   *   The default value to use if $name does not exist.
   *
   * @return mixed|\Drupal\bootstrap\Utility\StorageItem
   *   The cached value for $name.
   */
  public function getCache($name, $default = []) {
    static $cache = [];
    $theme = $this->getName();
    $theme_cache = self::getStorage();
    if (!isset($cache[$theme][$name])) {
      $value = $theme_cache->get($name);
      if (!isset($value)) {
        $value  = is_array($default) ? new StorageItem($default, $theme_cache) : $default;
        $theme_cache->set($name, $value);
      }
      $cache[$theme][$name] = $value;
    }
    return $cache[$theme][$name];
  }

  /**
   * Retrieves the theme info.
   *
   * @param string $property
   *   A specific property entry from the theme's info array to return.
   *
   * @return array
   *   The entire theme info or a specific item if $property was passed.
   */
  public function getInfo($property = NULL) {
    if (isset($property)) {
      return isset($this->info[$property]) ? $this->info[$property] : NULL;
    }
    return $this->info;
  }

  /**
   * Returns the machine name of the theme.
   *
   * @return string
   *   The machine name of the theme.
   */
  public function getName() {
    return $this->theme->getName();
  }

  /**
   * Returns the relative path of the theme.
   *
   * @return string
   *   The relative path of the theme.
   */
  public function getPath() {
    return $this->theme->getPath();
  }

  /**
   * Retrieves the CDN provider.
   *
   * @param string $provider
   *   A CDN provider name. Defaults to the provider set in the theme settings.
   *
   * @return \Drupal\bootstrap\Plugin\Provider\ProviderInterface|FALSE
   *   A provider instance or FALSE if there is no provider.
   */
  public function getProvider($provider = NULL) {
    $provider = $provider ?: $this->getSetting('cdn_provider');
    $provider_manager = new ProviderManager($this);
    if ($provider_manager->hasDefinition($provider)) {
      return $provider_manager->createInstance($provider, ['theme' => $this]);
    }
    return FALSE;
  }

  /**
   * Retrieves all CDN providers.
   *
   * @return \Drupal\bootstrap\Plugin\Provider\ProviderInterface[]
   *   All provider instances.
   */
  public function getProviders() {
    $providers = [];
    $provider_manager = new ProviderManager($this);
    foreach (array_keys($provider_manager->getDefinitions()) as $provider) {
      if ($provider === 'none') {
        continue;
      }
      $providers[$provider] = $provider_manager->createInstance($provider, ['theme' => $this]);
    }
    return $providers;
  }

  /**
   * Retrieves a theme setting.
   *
   * @param string $name
   *   The name of the setting to be retrieved.
   * @param bool $original
   *   Retrieve the original default value from code (or base theme config),
   *   not from the active theme's stored config.
   *
   * @return mixed
   *   The value of the requested setting, NULL if the setting does not exist.
   *
   * @see theme_get_setting()
   */
  public function getSetting($name, $original = FALSE) {
    if ($original) {
      return $this->settings()->getOriginal($name);
    }
    return $this->settings()->get($name);
  }

  /**
   * Retrieves the theme's setting plugin instances.
   *
   * @return \Drupal\bootstrap\Plugin\Setting\SettingInterface[]
   *   An associative array of setting objects, keyed by their name.
   */
  public function getSettingPlugins() {
    $settings = [];
    $setting_manager = new SettingManager($this);
    foreach (array_keys($setting_manager->getDefinitions()) as $setting) {
      $settings[$setting] = $setting_manager->createInstance($setting);
    }
    return $settings;
  }

  /**
   * Retrieves the theme's cache from the database.
   *
   * @return \Drupal\bootstrap\Utility\Storage
   *   The cache object.
   */
  public function getStorage() {
    static $cache = [];
    $theme = $this->getName();
    if (!isset($cache[$theme])) {
      $cache[$theme] = new Storage($theme);
    }
    return $cache[$theme];
  }

  /**
   * Retrieves the human-readable title of the theme.
   *
   * @return string
   *   The theme title or machine name as a fallback.
   */
  public function getTitle() {
    return $this->getInfo('name') ?: $this->getName();
  }

  /**
   * Determines whether or not if the theme has Bootstrap Framework Glyphicons.
   */
  public function hasGlyphicons() {
    $glyphicons = $this->getCache('glyphicons');
    if (!$glyphicons->has($this->getName())) {
      $exists = FALSE;
      foreach ($this->getAncestry(TRUE) as $ancestor) {
        if ($ancestor->getSetting('cdn_provider') || $ancestor->fileScan('/glyphicons-halflings-regular\.(eot|svg|ttf|woff)$/', NULL, ['ignore_flags' => FALSE])) {
          $exists = TRUE;
          break;
        }
      }
      $glyphicons->set($this->getName(), $exists);
    }
    return $glyphicons->get($this->getName(), FALSE);
  }

  /**
   * Includes a file from the theme.
   *
   * @param string $file
   *   The file name, including the extension.
   * @param string $path
   *   The path to the file in the theme. Defaults to: "includes". Set to FALSE
   *   or and empty string if the file resides in the theme's root directory.
   *
   * @return bool
   *   TRUE if the file exists and is included successfully, FALSE otherwise.
   */
  public function includeOnce($file, $path = 'includes') {
    static $includes = [];
    $file = preg_replace('`^/?' . $this->getPath() . '/?`', '', $file);
    $file = strpos($file, '/') !== 0 ? $file = "/$file" : $file;
    $path = is_string($path) && !empty($path) && strpos($path, '/') !== 0 ? $path = "/$path" : '';
    $include = DRUPAL_ROOT . '/' . $this->getPath() . $path . $file;
    if (!isset($includes[$include])) {
      $includes[$include] = !!@include_once $include;
      if (!$includes[$include]) {
        drupal_set_message(t('Could not include file: @include', ['@include' => $include]), 'error');
      }
    }
    return $includes[$include];
  }

  /**
   * Installs a Bootstrap based theme.
   */
  final protected function install() {
    $update_manager = new UpdateManager($this);
    $this->setSetting('schema', $update_manager->getLatestVersion());
  }

  /**
   * Removes a theme setting.
   *
   * @param string $name
   *   Name of the theme setting to remove.
   */
  public function removeSetting($name) {
    $this->settings()->clear($name)->save();
  }

  /**
   * Sets a value for a theme setting.
   *
   * @param string $name
   *   Name of the theme setting.
   * @param mixed $value
   *   Value to associate with the theme setting.
   */
  public function setSetting($name, $value) {
    $this->settings()->set($name, $value)->save();
  }

  /**
   * Retrieves the theme settings instance.
   *
   * @return \Drupal\bootstrap\ThemeSettings
   *   All settings.
   */
  public function settings() {
    static $themes = [];
    $name = $this->getName();
    if (!isset($themes[$name])) {
      $themes[$name] = new ThemeSettings($this, $this->themeHandler);
    }
    return $themes[$name];
  }

  /**
   * Determines whether or not a theme is a sub-theme of another.
   *
   * @param string|\Drupal\bootstrap\Theme $theme
   *   The name or theme Extension object to check.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function subthemeOf($theme) {
    return (string) $theme === $this->getName() || in_array($theme, array_keys(self::getAncestry()));
  }

}
