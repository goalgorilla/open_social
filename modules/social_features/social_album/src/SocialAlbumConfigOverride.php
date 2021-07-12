<?php

namespace Drupal\social_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides an overridden elements.
 *
 * @package Drupal\social_album
 */
class SocialAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Dependency to the field with reference to the album entity.
   */
  const DEPENDENCY = [
    'dependencies' => [
      'config' => [
        'field.field.post.photo.field_album' => 'field.field.post.photo.field_album',
      ],
    ],
  ];

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

    $config_names = [
      'core.entity_form_display.post.photo.default',
      'core.entity_form_display.post.photo.group',
      'core.entity_form_display.post.photo.profile',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $fields = $this->configFactory->getEditable($config_name)
          ->get('content');

        $weight = $fields['field_post_image']['weight'] + 1;

        foreach ($fields as &$field) {
          if ($field['weight'] >= $weight) {
            $field['weight']++;
          }
        }

        $fields['field_album'] = [
          'weight' => $weight,
          'settings' => [],
          'third_party_settings' => [],
          'type' => 'social_album_options_select',
          'region' => 'content',
        ];

        $overrides[$config_name] = self::DEPENDENCY + ['content' => $fields];
      }
    }

    $config_name = 'core.entity_view_display.post.photo.default';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = self::DEPENDENCY + [
        'hidden' => [
          'field_album' => TRUE,
        ],
      ];
    }

    $config_name = 'core.entity_view_display.post.photo.activity';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = self::DEPENDENCY + [
        'dependencies' => [
          'module' => [
            'social_album' => 'social_album',
          ],
        ],
        'content' => [
          'field_album' => [
            'type' => 'social_album_entity_reference_label',
            'weight' => 5,
            'region' => 'content',
            'label' => 'hidden',
            'settings' => [
              'link' => TRUE,
            ],
            'third_party_settings' => [],
          ],
        ],
      ];
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
      'search_api.index.social_all',
      'search_api.index.social_content',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'datasource_settings' => [
            'entity:node' => [
              'bundles' => [
                'selected' => [
                  'album' => 'album',
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_names = [
      'core.entity_form_display.node.album.default',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        // Add the field to the content.
        $content = $config->get('content');
        $content['groups'] = [];
        $content['groups']['type'] = 'social_group_selector_widget';
        $content['groups']['settings'] = [];
        $content['groups']['region'] = 'content';
        $content['groups']['third_party_settings'] = [];

        $overrides[$config_name] = [
          'content' => $content,
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
