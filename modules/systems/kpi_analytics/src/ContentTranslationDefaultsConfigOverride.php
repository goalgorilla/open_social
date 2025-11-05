<?php

namespace Drupal\kpi_analytics;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides content translation for the KPI Analytics module.
 *
 * @package Drupal\kpi_analytics
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    $overrides = [];

    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    $is_content_translations_enabled = \Drupal::moduleHandler()->moduleExists('social_content_translation');

    if ($is_content_translations_enabled) {

      $config_name = 'language.content_settings.block_content.kpi_analytics';
      if (in_array($config_name, $names, TRUE)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'content_translation' => [
              'enabled' => TRUE,
            ],
          ],
        ];
      }

      $config_name = 'core.base_field_override.block_content.kpi_analytics.info';
      if (in_array($config_name, $names, TRUE)) {
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
    return 'kpi_analytics.content_translation_defaults_config_override';
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
