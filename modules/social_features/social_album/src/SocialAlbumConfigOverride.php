<?php

namespace Drupal\social_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialAlbumConfigOverride.
 *
 * @package Drupal\social_album
 */
class SocialAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

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

    $config_name = 'like_and_dislike.settings';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'enabled_types' => [
          'node' => [
            'album' => 'album',
          ],
        ],
      ];
    }

    $config_names = [
      'block.block.socialblue_profile_hero_block',
      'block.block.socialblue_pagetitleblock_content',
      'block.block.socialblue_profile_statistic_block',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'visibility' => [
            'request_path' => [
              'pages' => $this->configFactory->getEditable($config_name)->get('visibility.request_path.pages') . "\r\n/user/*/albums",
            ],
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
