<?php

namespace Drupal\social_landing_page;

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

    $settings = \Drupal::configFactory()->getEditable('social_content_translation.settings');
    $translate_book = $settings->getOriginal('social_landing_page', FALSE);

    // If the social_content_translation settings object doesn't exist or we are
    // disabled then we perform no overrides.
    if ($translate_book) {
      $this->addTranslationOverrides($names, $overrides);
    }

    return $overrides;
  }

  /**
   * Adds the overrides for this config overrides for field translations.
   *
   * By making this a separate method it can easily be overwritten in child
   * classes without having to duplicate the logic of whether it should be
   * invoked.
   *
   * @param array $names
   *   The names of the configuration keys for which overwrites are requested.
   * @param array $overrides
   *   The array of overrides that should be adjusted.
   */
  protected function addTranslationOverrides(array $names, array &$overrides) {
    $field_overrides = [
      'core.base_field_override.node.landing_page.title' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.landing_page.menu_link' => [
        'translatable' => TRUE,
      ],
      'core.base_field_override.node.landing_page.path' => [
        'translatable' => TRUE,
      ],
      'field.field.node.landing_page.body' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.block.field_block_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.button.field_button_link_an' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.button.field_button_link_lu' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.featured.field_featured_description' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.featured.field_featured_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.hero.field_hero_image' => [
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
      'field.field.paragraph.hero.field_hero_subtitle' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.hero.field_hero_title' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.introduction.field_introduction_text' => [
        'translatable' => TRUE,
      ],
      'field.field.paragraph.introduction.field_introduction_title' => [
        'translatable' => TRUE,
      ],
    ];

    foreach ($field_overrides as $name => $override) {
      if (in_array($name, $names)) {
        $overrides[$name] = $override;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return __CLASS__;
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
    return NULL;
  }

}
