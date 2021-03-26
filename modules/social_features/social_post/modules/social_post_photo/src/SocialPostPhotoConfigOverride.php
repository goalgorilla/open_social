<?php

namespace Drupal\social_post_photo;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class SocialPostPhotoConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   * @codingStandardsIgnoreStart
   */
  public function loadOverrides($names) {
    // @codingStandardsIgnoreEnd
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    // Override postblocks on activity streams.
    $config_names = [
      'block.block.postblock' => 'post_photo_block',
      'block.block.postongroupblock' => 'post_photo_group_block',
      'block.block.postonprofileblock' => 'post_photo_profile_block',
    ];
    foreach ($config_names as $config_name => $plugin) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'plugin' => $plugin,
        ];
      }
    }

    // Override message templates.
    $config_names = [
      'message.template.create_post_community',
      'message.template.create_post_group',
      'message.template.create_post_profile',
      'message.template.create_post_profile_stream',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);

        $entities = $config->get('third_party_settings.activity_logger.activity_bundle_entities');
        // Only override if the configuration for posts exist.
        if ($entities['post-post'] == 'post-post') {
          $entities['post-photo'] = 'post-photo';

          $overrides[$config_name] = [
            'third_party_settings' => [
              'activity_logger' => [
                'activity_bundle_entities' => $entities,
              ],
            ],
          ];
        }
      }
    }

    // Override like and dislike settings.
    $config_name = 'like_and_dislike.settings';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);
      // Get enabled post bundles.
      $post_types = $config->get('enabled_types.post');
      $post_types['photo'] = 'photo';

      $overrides[$config_name] = [
        'enabled_types' => [
          'post' => $post_types,
        ],
      ];
    }

    $config_names = [
      'core.entity_view_display.post.photo.activity',
      'core.entity_view_display.post.photo.featured',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $route_match = \Drupal::routeMatch();
        if ($route_match->getRouteName() === 'entity.node.canonical' && $route_match->getParameter('node')->bundle() === 'dashboard') {
          $overrides[$config_name] = [
            'content' => [
              'field_post' => [
                'type' => 'smart_trim',
                'settings' => [
                  'more_class' => 'more-link',
                  'more_link' => TRUE,
                  'more_text' => '',
                  'summary_handler' => 'full',
                  'trim_length' => 250,
                  'trim_options' =>
                    [
                      'text' => FALSE,
                      'trim_zero' => FALSE,
                    ],
                  'trim_suffix' => '...',
                  'trim_type' => 'chars',
                  'wrap_class' => 'trimmed',
                  'wrap_output' => FALSE,
                ],
              ],
            ],
          ];
        }
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPostPhotoConfigOverride';
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
