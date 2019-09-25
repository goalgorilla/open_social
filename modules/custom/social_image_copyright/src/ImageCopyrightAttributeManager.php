<?php

namespace Drupal\social_image_copyright;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ImageCopyrightAttributeManager.
 */
class ImageCopyrightAttributeManager extends DefaultPluginManager {

  /**
   * ImageCopyrightAttributeManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'image_copyright_attribute', ['image_copyright_attribute']);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Make sure each plugin definition had at least a field type.
    if (empty($definition['type'])) {
      $definition['type'] = 'textfield';
    }
  }

}
