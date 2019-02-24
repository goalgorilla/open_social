<?php

namespace Drupal\social_comment_upload;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // We don't need to add any fields if uploads are disabled.
    // We use getEditable and getOriginal to avoid override loops.
    if ($this->configFactory->getEditable('social_comment_upload.settings')->getOriginal('allow_upload_comments', TRUE) == FALSE) {
      return $overrides;
    }

    // Add field_group and field_comment_files.
    $config_name = 'core.entity_form_display.comment.comment.default';
    if (in_array($config_name, $names)) {
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

      $content = [
        'field_comment_files' => [
          'weight' => 1,
          'settings' => [
            'progress_indicator' => 'throbber',
          ],
          'third_party_settings' => [],
          'type' => 'file_image_table',
          'region' => 'content',
        ],
      ];

      $overrides[$config_name] = [
        'third_party_settings' => $third_party,
        'content' => $content,
      ];

    }

    // Add field_comment_files.
    $config_name = 'core.entity_view_display.comment.comment.default';
    if (in_array($config_name, $names)) {
      $content = [
        'field_comment_files' => [
          'weight' => 1,
          'label' => 'hidden',
          'settings' => [],
          'third_party_settings' => [],
          'type' => 'file_image_table',
          'region' => 'content',
        ],
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
