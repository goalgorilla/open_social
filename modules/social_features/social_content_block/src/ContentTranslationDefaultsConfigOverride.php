<?php

namespace Drupal\social_content_block;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hux\HuxModuleHandler;

/**
 * Provides content translation for the Social Content Block module.
 *
 * @package Drupal\social_content_block
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs the service with DI.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
  ) {
    $this->moduleHandler = ($module_handler instanceof HuxModuleHandler) ? \Drupal::service('module_handler.drupal_core') : $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    $is_content_translations_enabled = $this->moduleHandler
      ->moduleExists('social_content_translation');

    if (!$is_content_translations_enabled) {
      return $overrides;
    }

    // Translations for "Custom content list block" custom block.
    $config_name = 'language.content_settings.block_content.custom_content_list';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.block_content.custom_content_list.info';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'social_content_block.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    // The interface says we should return an object here, but we don't care and
    // this does not seem to break anything?
    // @phpstan-ignore-next-line
    return NULL;
  }

}
