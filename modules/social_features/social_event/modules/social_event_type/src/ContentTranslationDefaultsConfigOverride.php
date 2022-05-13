<?php

namespace Drupal\social_event_type;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides content translation for the Social Event Type module.
 *
 * @package Drupal\social_event_type
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ContentTranslationDefaultsConfigOverride constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    $overrides = [];

    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    if (!$this->moduleHandler->moduleExists('social_content_translation')) {
      return $overrides;
    }

    // Translations for "Event types" vocabulary.
    $config_name = 'language.content_settings.taxonomy_term.event_types';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }

    $config_names = [
      'core.base_field_override.taxonomy_term.event_types.name',
      'core.base_field_override.taxonomy_term.event_types.changed',
      'core.base_field_override.taxonomy_term.event_types.description',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix(): string {
    return 'social_event_type.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name): CacheableMetadata {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}
