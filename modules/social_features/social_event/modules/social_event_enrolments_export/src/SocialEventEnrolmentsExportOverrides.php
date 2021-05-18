<?php

namespace Drupal\social_event_enrolments_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialEventEnrolmentsExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.event_manage_enrollments';

    if (in_array($config_name, $names)) {
      $selected_actions['social_event_enrolments_export_enrollments_action'] = [
        'action_id' => 'social_event_enrolments_export_enrollments_action',
        'preconfiguration' => [
          'label_override' => 'Export',
        ],
      ];

      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'social_views_bulk_operations_bulk_form_enrollments_1' => [
                  'selected_actions' => $selected_actions,
                ],
              ],
            ],
          ],
        ],
      ];
    }

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
