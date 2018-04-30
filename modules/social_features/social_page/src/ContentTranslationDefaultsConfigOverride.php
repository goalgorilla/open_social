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

    $settings = \Drupal::configFactory()->getEditable('social_language_content.settings');
    $translate_book = $settings->getOriginal('social_page', FALSE);

    // If the social_language_content settings object doesn't exist or we are
    // disabled then we perform no overrides.
    if ($translate_book) {
      $this->addTranslationOverrides($names, $overrides);
    }

    return $overrides;
  }

  protected function addTranslationOverrides($names, array &$overrides) {
//    $field_overrides = [
//      'core.base_field_override.node.page.title' => [
//        'translatable' => TRUE,
//      ],
//      'core.base_field_override.node.page.menu_link' => [
//        'translatable' => TRUE,
//      ],
//      'core.base_field_override.node.page.path' => [
//        'translatable' => TRUE,
//      ],
//      'field.field.node.page.body' => [
//        'translatable' => TRUE,
//      ],
//      'field.field.node.page.field_page_image' => [
//        'third_party_settings' => [
//          'content_translation' => [
//            'translation_sync' => [
//              'file' => 'file',
//              'alt' => '0',
//              'title' => '0',
//            ],
//          ],
//        ],
//        'translatable' => TRUE,
//      ],
//    ];
//
//    foreach ($field_overrides as $name => $override) {
//      if (in_array($name, $names)) {
//        $overrides[$name] = $override;
//      }
//    }
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
