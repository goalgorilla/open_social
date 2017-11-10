<?php

namespace Drupal\social_comment_upload;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialCommentUploadConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_comment_upload
 */
class SocialCommentUploadConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    // Add field_group and field_comment_files.
    $config_name = 'core.entity_form_display.comment.comment.default';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $third_party = $config->get('third_party_settings');

      $third_party = [
        'field_group' => [
          'group_add_attachment' => [
            'children' => [
              'field_comment_files',
            ],
            'parent_name' => '',
            'weight' => 20,
            'format_type' => 'details',
            'format_settings' => [
              'label' => 'Add attachment',
              'required_fields' => TRUE,
              'id' => '',
              'classes' => 'comment-attachments',
              'open' => FALSE,
            ],
            'label' => 'Add attachment',
          ],
        ],
      ];

      $content = $config->get('content');
      $content['field_comment_files'] = [
        'weight' => 1,
        'settings' => [
          'progress_indicator' => 'throbber',
        ],
        'third_party_settings' => [],
        'type' => 'file_generic',
        'region' => 'content',
      ];

      $overrides[$config_name]['third_party_settings'] = $third_party;
      $overrides[$config_name]['content'] = $content;
    }

    // Add field_comment_files.
    $config_name = 'core.entity_view_display.comment.comment.default';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $content = $config->get('content');
      $content['field_comment_files'] = [
        'weight' => 1,
        'label' => 'hidden',
        'settings' => [],
        'third_party_settings' => [],
        'type' => 'file_default',
        'region' => 'content',
      ];

      $overrides[$config_name] = [
        'content' => $content,
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialCommentUploadConfigOverride';
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
