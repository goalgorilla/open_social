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
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

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
      $third_party = $config->get('third_party_settings');
      if (isset($third_party['field_group']['group_profile_names_image'])) {
        $third_party['field_group']['group_profile_names_image']['children'][] = 'field_profile_nick_name';

        // We use the entire children array because a deep merge on a numerical
        // key array doesn't work.
        $children = $third_party['field_group']['group_profile_names_image']['children'];
        $children[] = 'field_profile_nick_name';
        $overrides[$config_name]['third_party_settings']['field_group']['group_profile_names_image']['children'] = $children;
      }
    }

    // Add field_group and field_comment_files.
    $config_names = [
      'search_api.index.social_all' => '',
      'search_api.index.social_users' => '.settings',
    ];

    foreach ($config_names as $config_name => $suffix) {
      if (in_array($config_name, $names)) {
        $config = $config_factory->getEditable($config_name);
        $processor_settings = [];

        foreach (['ignorecase', 'tokenizer', 'transliteration'] as $processor) {
          $fields = $config->get('processor_settings.' . $processor . $suffix . '.fields');
          $fields[] = 'field_profile_nick_name';

          if ($suffix) {
            $processor_settings[$processor]['settings']['fields'] = $fields;
          }
          else {
            $processor_settings[$processor]['fields'] = $fields;
          }
        }

        $overrides[$config_name] = [
          'dependencies' => [
            'config' => array_merge([
              'field.storage.profile.field_profile_nick_name',
            ], $config->get('dependencies.config')),
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
          'processor_settings' => $processor_settings,
        ];
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
