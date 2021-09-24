<?php

namespace Drupal\social_featured_items;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides content translation for the Social Featured Items module.
 *
 * @package Drupal\social_featured_items
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the service with DI.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandler $module_handler) {
    $this->moduleHandler = $module_handler;
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

    // Translations for "Featured Items" custom block.
    $config_name = 'language.content_settings.block_content.featured_items';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.block_content.featured_items.info';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }

    // Translations for "Featured Item" paragraph type.
    $config_name = 'language.content_settings.paragraph.featured_item';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.featured_item.status';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }
    $config_name = 'field.field.paragraph.featured_item.field_featured_item_image';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
      ];
    }

    // Translations for "Featured Items" paragraph type.
    $config_name = 'language.content_settings.paragraph.featured_items';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.featured_items.status';
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
    return 'social_featured_items.content_translation_defaults_config_override';
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
