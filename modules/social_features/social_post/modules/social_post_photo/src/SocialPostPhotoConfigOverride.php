<?php

namespace Drupal\social_post_photo;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Example configuration override.
 */
class SocialPostPhotoConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialPostPhotoConfigOverride constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory) {
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
  }

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
        $config = $this->configFactory->getEditable($config_name);

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
      $config = $this->configFactory->getEditable($config_name);
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
        if ($this->routeMatch->getRouteName() === 'entity.node.canonical' && $this->routeMatch->getParameter('node')->bundle() === 'dashboard') {
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
   * Creates a configuration object for use during install and synchronization.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase|null
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
