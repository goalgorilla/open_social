<?php

namespace Drupal\social_user_export\Annotation;

use Drupal\Component\Annotation\AnnotationBase;

/**
 * Defines dependencies for a plugin.
 *
 * @see \Drupal\social_user_export\Plugin\UserExportPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class PluginDependency extends AnnotationBase {

  /**
   * Modules that should be enabled for the plugin to be enabled.
   */
  public ?array $module = NULL;

  /**
   * Config that should exist and be enabled for the plugin to be enabled.
   */
  public ?array $config = NULL;

  /**
   * Themes that should be enabled for the plugin to be enabled.
   */
  public ?array $theme = NULL;

  /**
   * {@inheritdoc}
   */
  public function get() : array {
    $dependencies = [];

    if ($this->module !== NULL) {
      $dependencies['module'] = $this->module;
    }

    if ($this->config !== NULL) {
      $dependencies['config'] = $this->config;
    }

    if ($this->theme !== NULL) {
      $dependencies['theme'] = $this->theme;
    }

    return $dependencies;
  }

}
