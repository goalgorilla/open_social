<?php

namespace Drupal\social_user_export;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialUserExportOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.user_admin_people';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'views_bulk_operations_bulk_form' => [
                  'selected_actions' => [
                    'social_user_export_user_action' => 'social_user_export_user_action',
                  ],
                  'preconfiguration' => [
                    'social_user_export_user_action' => [
                      'label_override' => '',
                    ],
                  ],
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
    return 'SocialUserExportOverrides';
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
