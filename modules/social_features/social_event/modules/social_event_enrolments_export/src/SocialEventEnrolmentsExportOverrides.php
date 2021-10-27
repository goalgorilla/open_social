<?php

namespace Drupal\social_event_enrolments_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 *
 * @deprecated in social:10.2.0 and is removed from social:11.0.0. Use
 *   _social_event_enrolments_export_alter_event_manage_enrollments_view()
 *   instead.
 *
 * @todo Change @see to point to a change record.
 * @see _social_event_enrolments_export_alter_event_manage_enrollments_view()
 */
class SocialEventEnrolmentsExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Code has been moved to
    // _social_event_enrolments_export_alter_event_manage_enrollments_view().
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEventEnrolmentsExportOverrides';
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
