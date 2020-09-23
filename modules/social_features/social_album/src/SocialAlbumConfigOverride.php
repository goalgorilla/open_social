<?php

namespace Drupal\social_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialAlbumConfigOverride.
 *
 * @package Drupal\social_album
 */
class SocialAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'core.entity_form_display.post.photo.default';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'config' => [
            'field.field.post.photo.field_album' => 'field.field.post.photo.field_album',
          ],
        ],
        'content' => [
          'field_album' => [
            'weight' => 2,
            'settings' => [],
            'third_party_settings' => [],
            'type' => 'options_select',
            'region' => 'content',
          ],
        ],
      ];
    }

    $config_names = [
      'core.entity_form_display.post.photo.group',
      'core.entity_form_display.post.photo.profile',
      'core.entity_view_display.post.photo.default',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'dependencies' => [
            'config' => [
              'field.field.post.photo.field_album' => 'field.field.post.photo.field_album',
            ],
          ],
          'hidden' => [
            'field_album' => TRUE,
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialAlbumConfigOverride';
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
