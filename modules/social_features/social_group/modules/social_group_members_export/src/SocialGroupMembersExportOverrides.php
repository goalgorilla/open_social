<?php

namespace Drupal\social_group_members_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 *
 * @deprecated in social:10.2.0 and is removed from social:11.0.0. Use
 *   _social_group_members_export_alter_group_manage_members_view() instead.
 *
 * @todo Change @see to point to a change record.
 * @see _social_group_members_export_alter_group_manage_members_view()
 */
class SocialGroupMembersExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Code has been moved to
    // _social_group_members_export_alter_group_manage_members_view().
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGroupMembersExportOverrides';
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
