<?php

namespace Drupal\social_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides content translation defaults for the basic page content type.
 *
 * @package Drupal\social_page
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // This setting can't be changed in an override because that would create
    // and endless loop in trying to apply the override.
    $settings = \Drupal::configFactory()->getEditable('social_content_translation.settings');
    $translate_book = $settings->getOriginal('social_page', FALSE);

    // If the social_content_translation settings object doesn't exist or we are
    // disabled then we perform no overrides.
    if ($translate_book) {
      $translation_overrides = $this->getTranslationOverrides();

      foreach ($translation_overrides as $name => $override) {
        if (in_array($name, $names)) {
          $overrides[$name] = $override;
        }
      }
    }

    return $overrides;
  }

  /**
   * Returns the configuration override for this module's translations.
   *
   * By making this a separate method it can easily be overwritten in child
   * classes without having to duplicate the logic of whether it should be
   * invoked.
   *
   * @return array
   *   An array keyed by configuration name with the override as value.
   */
  protected function getTranslationOverrides() {
    return [
      'language.content_settings.node.page' => [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      'core.base_field_override.node.page.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.page.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.page.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.page.body' => [
        'translatable' => TRUE,
      ],
      'field.field.node.page.field_page_image' => [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
        'translatable' => TRUE,
      ],
    ];
  }

  /**
   * Returns the configurations that are overridden in this class.
   *
   * @return array
   *   An array of configuration names.
   */
  protected function getOverriddenConfigurations() {
    return array_keys($this->getTranslationOverrides());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'social_page.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    if (in_array($name, $this->getOverriddenConfigurations())) {
      $metadata->addCacheTags(['config:social_content_translation.settings']);
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
