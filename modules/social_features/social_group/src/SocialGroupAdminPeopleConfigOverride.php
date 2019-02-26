<?php

namespace Drupal\social_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialGroupAdminPeopleConfigOverride.
 *
 * @package Drupal\social_group
 */
class SocialGroupAdminPeopleConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.user_admin_people';

    // Add AddMembersToGroup to VBO Admin people.
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'views_bulk_operations_bulk_form' => [
                  'selected_actions' => [
                    'social_group_add_members_to_group_action' => 'social_group_add_members_to_group_action',
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
    return 'SocialGroupAdminPeopleConfigOverride';
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
