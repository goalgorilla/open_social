<?php

namespace Drupal\social_group_members_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialGroupMembersExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.group_manage_members';

    if (in_array($config_name, $names)) {
      $selected_actions['social_group_members_export_member_action'] = [
        'action_id' => 'social_group_members_export_member_action',
        'preconfiguration' => [
          'label_override' => 'Export',
        ],
      ];

      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'social_views_bulk_operations_bulk_form_group' => [
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
