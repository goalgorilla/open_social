<?php

namespace Drupal\social_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Configuration override for people admin overview.
 *
 * @package Drupal\social_group
 */
class SocialGroupAdminPeopleConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.user_admin_people';

    // Add AddMembersToGroup to VBO Admin people.
    if (in_array($config_name, $names, TRUE)) {
      // Get the current selected actions.
      $selected_actions['social_group_add_members_to_group_action'] = [
        'action_id' => 'social_group_add_members_to_group_action',
      ];

      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'views_bulk_operations_bulk_form' => [
                  'selected_actions' => $selected_actions,
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.group_manage_members';
    // Add all available group types on the platform here, so they can all
    // make use of the new manage members overview.
    if (in_array($config_name, $names, TRUE)) {
      $social_group_types = [
        'open_group',
        'closed_group',
        'public_group',
      ];
      $this->moduleHandler->alter('social_group_types', $social_group_types);
      // Loop over all group types.
      foreach ($social_group_types as $group_type) {
        $membership = $group_type . '-group_membership';
        // Add each group type to the filters of this overview.
        $overrides[$config_name]['display']['default']['display_options']['filters']['type']['value'][$membership] = $membership;
      }
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
