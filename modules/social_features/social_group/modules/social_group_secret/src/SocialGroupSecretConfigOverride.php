<?php

namespace Drupal\social_group_secret;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialGroupSecretConfigOverride.
 *
 * @package Drupal\social_group_secret
 */
class SocialGroupSecretConfigOverride implements ConfigFactoryOverrideInterface {

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
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_groups',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'field_settings' => [
            'rendered_item' => [
              'configuration' => [
                'view_mode' => [
                  'entity:group' => [
                    'secret_group' => 'teaser',
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_names = [
      'views.view.search_all',
      'views.view.search_groups',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'display' => [
            'default' => [
              'display_options' => [
                'row' => [
                  'options' => [
                    'view_modes' => [
                      'entity:group' => [
                        'secret_group' => 'teaser',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_names = [
      'message.template.create_content_in_joined_group',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names, FALSE)) {
        $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
          [
            'group_content-secret_group-group_node-event' => 'group_content-secret_group-group_node-event',
            'group_content-secret_group-group_node-topic' => 'group_content-secret_group-group_node-topic',
          ];
      }
    }

    $config_name = 'message.template.join_to_group';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-secret_group-group_membership' => 'group_content-secret_group-group_membership',
        ];
    }

    $config_name = 'message.template.invited_to_join_group';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-secret_group-group_invitation' => 'group_content-secret_group-group_invitation',
        ];
    }

    $config_name = 'views.view.newest_groups';

    $displays = [
      'page_all_groups',
      'block_newest_groups',
    ];

    if (in_array($config_name, $names)) {
      foreach ($displays as $display_name) {
        $overrides[$config_name] = [
          'display' => [
            $display_name => [
              'cache_metadata' => [
                'contexts' => [
                  'user' => 'user',
                ],
              ],
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
    return 'SocialGroupSecretConfigOverride';
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
