<?php

namespace Drupal\social_file_private;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StreamWrapper\PrivateStream;

/**
 * Class SocialFilePrivateFieldsConfigOverride.
 *
 * Override the field.storage configuration and set private uri_scheme.
 *
 * @package Drupal\social_file_private
 */
class SocialFilePrivateFieldsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Get all the file and image fields to protect.
   *
   * @TODO Retrieve the file and image fields programmatically.
   *
   * Note: this list is now fixed, but an error will be shown in the status
   * report when there are fields of type image, file using public uri_scheme.
   *
   * @return array
   *    Returns an array containing config_names.
   */
  public function getFileImageFieldsToProtect() {
    // We want to override all the known file and image uploads.
    $config_names = [
      'field.storage.block_content.field_hero_image',
      'field.storage.comment.field_comment_files',
      'field.storage.group.field_group_image',
      'field.storage.node.field_book_image',
      'field.storage.node.field_event_image',
      'field.storage.node.field_files',
      'field.storage.node.field_page_image',
      'field.storage.node.field_topic_image',
      'field.storage.post.field_post_image',
      'field.storage.profile.field_profile_image',
    ];
    return $config_names;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (PrivateStream::basePath()) {
      $config_names = $this->getFileImageFieldsToProtect();
      foreach ($config_names as $config_name) {
        if (in_array($config_name, $names)) {
          $config = \Drupal::service('config.factory')->getEditable($config_name);
          $uri_scheme = $config->get('settings.uri_scheme');
          if ($uri_scheme == 'public') {
            $overrides[$config_name]['settings']['uri_scheme'] = 'private';
          }
        }
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFilePrivateFieldsConfigOverride';
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
