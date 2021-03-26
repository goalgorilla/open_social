<?php

namespace Drupal\social_comment;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class SocialCommentConfigOverride implements ConfigFactoryOverrideInterface {

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
    $overrides = [];

    $config_name = 'core.entity_view_display.comment.comment.activity_comment';

    if (in_array($config_name, $names)) {
      $route_match = \Drupal::routeMatch();
      if ($route_match->getRouteName() === 'entity.node.canonical' && $route_match->getParameter('node')->bundle() === 'dashboard') {
        $overrides[$config_name] = [
          'content' => [
            'field_comment_body' => [
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
            'links' => [
              'region' => 'hidden',
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
    return 'SocialCommentConfigOverride';
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
