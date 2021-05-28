<?php

namespace Drupal\social_profile_fields;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialProfileFieldsConfigOverride.
 *
 * Override profile form.
 *
 * @package Drupal\social_profile_fields
 */
class SocialProfileFieldsOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    // Add field_group and field_comment_files.
    $config_name = 'core.entity_form_display.profile.profile.default';
    if (in_array($config_name, $names, TRUE)) {
      // Add the nick name field to the profile.
      $overrides[$config_name]['content']['field_profile_nick_name'] = [
        'weight' => 0,
        'settings' => [
          'size' => '60',
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'type' => 'string_textfield',
        'region' => 'content',
      ];

      // If there is a profile names and image field_group we move the field.
      $third_party = $config_factory->getEditable($config_name)->get('third_party_settings');
      if (isset($third_party['field_group']['group_profile_names_image'])) {
        $overrides[$config_name]['third_party_settings']['field_group']['group_profile_names_image']['children']['field_profile_nick_name'] = 'field_profile_nick_name';
      }
    }

    // Add field_group and field_comment_files.
    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_users',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names, TRUE)) {

        // Add the config overrides (deeply merged) needed to add the field.
        $overrides[$config_name] = [
          'dependencies' => [
            'config' => [
              'field.storage.profile.field_profile_nick_name' => 'field.storage.profile.field_profile_nick_name',
            ],
          ],
          'field_settings' => [
            'field_profile_nick_name' => [
              'label' => 'Nickname',
              'datasource_id' => 'entity:profile',
              'property_path' => 'field_profile_nick_name',
              'type' => 'text',
              'dependencies' => [
                'config' => [
                  'field.storage.profile.field_profile_nick_name',
                ],
              ],
            ],
          ],
        ];

        // Configure the relevant processors for our field.
        $processor_settings = $config_factory->getEditable($config_name)
          ->get('processor_settings');
        // In some scenarios (e.g. site install) this setting may not exist and
        // be a NULL value.
        if (empty($processor_settings)) {
          $processor_settings = [];
        }
        $enabled_processors = array_intersect(
          // We want to configure the following processors if they're enabled.
          ['ignorecase', 'tokenizer', 'transliteration'],
          array_keys($processor_settings)
        );

        foreach ($enabled_processors as $processor) {
          // If this processor has not specified specific fields then our field
          // is already automatically included.
          if (!isset($processor_settings[$processor]['fields'])) {
            continue;
          }
          $overrides[$config_name]['processor_settings'][$processor]['fields']['field_profile_nick_name'] = 'field_profile_nick_name';
        }
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialProfileFieldsOverride';
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
