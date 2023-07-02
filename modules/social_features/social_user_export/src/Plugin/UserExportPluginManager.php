<?php

namespace Drupal\social_user_export\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_user_export\Annotation\UserExportPlugin;

/**
 * Provides the User export plugin plugin manager.
 */
class UserExportPluginManager extends DefaultPluginManager {

  /**
   * The Drupal theme handler.
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new UserExportPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The Drupal theme handler to find enabled themes.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory to check configuration status.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/UserExportPlugin', $namespaces, $module_handler, UserExportPluginInterface::class, UserExportPlugin::class);
    $this->alterInfo('social_user_export_plugin_info');
    $this->setCacheBackend($cache_backend, 'social_user_export_user_export_plugin_plugins');
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array &$definitions
   */
  public function alterDefinitions(&$definitions) : void {
    // Call any alter hook that's configured.
    parent::alterDefinitions($definitions);

    // We want to exclude any definitions that have a dependency on something
    // that is not enabled.
    $definitions = array_filter(
      $definitions,
      function (array $definition) {
        if (!isset($definition['dependencies'])) {
          return TRUE;
        }

        $module_dependencies = $definition['dependencies']['module'] ?? [];
        foreach ($module_dependencies as $module_dependency) {
          // If any of the dependent modules are disabled we filter out the
          // plugin.
          if (!$this->moduleHandler->moduleExists($module_dependency)) {
            return FALSE;
          }
        }

        $theme_dependencies = $definition['dependencies']['theme'] ?? [];
        foreach ($theme_dependencies as $theme_dependency) {
          // If any of the dependent themes are disabled we filter out the
          // plugin.
          if (!$this->themeHandler->themeExists($theme_dependency)) {
            return FALSE;
          }
        }

        $config_dependencies = $definition['dependencies']['config'] ?? [];
        foreach ($config_dependencies as $config_dependency_id) {
          // If any of the dependent config items don't exist or are disabled
          // we filter out the plugin.
          $config_dependency = $this->configFactory->get($config_dependency_id);
          $config_status = $config_dependency->get('status');
          if ($config_dependency->isNew() || (is_bool($config_status) && !$config_status)) {
            return FALSE;
          }
        }

        return TRUE;
      }
    );
  }

}
