<?php

namespace Drupal\social_download_count;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialDownloadCountConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_download_count
 */
class SocialDownloadCountConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Set private file system to files field.
    $config_name = 'field.storage.node.field_files';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = ['settings' => ['uri_scheme' => 'private']];
    }

    // Set download count widget to files fields.
    $content_types = ['book', 'event', 'page', 'topic'];
    foreach ($content_types as $content_type) {
      $config_name = "core.entity_view_display.node.{$content_type}.default";
      $overrides[$config_name] = ['content' => ['field_files' => ['type' => 'FieldDownloadCount']]];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialDownloadCountConfigOverride';
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
