<?php

namespace Drupal\social_tagging;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class SocialTaggingOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'views.view.search_content',
      'search_api.index.social_content',
    ];

    $found = FALSE;

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $found = TRUE;
        break;
      }
    }

    if (!$found) {
      return $overrides;
    }

    if (in_array($config_name = $config_names[1], $names)) {
      $field_settings = \Drupal::service('config.factory')->getEditable($config_name)
        ->get('field_settings');

      $field_settings['social_tagging'] = [
        'label' => 'Tags',
        'datasource_id' => 'entity:node',
        'property_path' => 'social_tagging',
        'type' => 'integer',
      ];

      $overrides[$config_name]['field_settings'] = $field_settings;
    }

    $tag_service = \Drupal::service('social_tagging.tag_service');

    // Check if tagging is active.
    if (!($tag_service->active() && $tag_service->hasContent())) {
      return $overrides;
    }

    $fields = [];

    if ($tag_service->allowSplit()) {
      foreach ($tag_service->getCategories() as $tid => $value) {
        if (!empty($tag_service->getChildren($tid))) {
          $fields['social_tagging_' . $tid] = [
            'identifier' => social_tagging_to_machine_name($value),
            'label' => $value,
          ];
        }
      }
    }
    else {
      $fields['social_tagging'] = [
        'identifier' => 'tag',
        'label' => 'Tags',
      ];
    }

    if (!in_array($config_name = $config_names[0], $names)) {
      return $overrides;
    }

    $overrides[$config_name]['dependencies']['config'][] = 'taxonomy.vocabulary.social_tagging';

    foreach (['default', 'page', 'page_no_value'] as $display) {
      $overrides[$config_name]['display'][$display]['cache_metadata']['contexts'][] = 'user';
    }

    $group = 1;

    if (count($fields) > 1) {
      $overrides[$config_name]['display']['default']['display_options']['filter_groups']['groups'][1] = 'AND';
      $overrides[$config_name]['display']['default']['display_options']['filter_groups']['groups'][2] = 'OR';
      $group++;
    }

    foreach ($fields as $field => $data) {
      $overrides[$config_name]['display']['default']['display_options']['filters'][$field] = [
        'id' => $field,
        'table' => 'search_api_index_social_content',
        'field' => 'social_tagging',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'or',
        'value' => [],
        'group' => $group,
        'exposed' => TRUE,
        'expose' => [
          'operator_id' => $field . '_op',
          'label' => $data['label'],
          'description' => '',
          'use_operator' => FALSE,
          'operator' => $field . '_op',
          'identifier' => $data['identifier'],
          'required' => FALSE,
          'remember' => FALSE,
          'multiple' => TRUE,
          'remember_roles' => [
            'authenticated' => 'authenticated',
            'anonymous' => '0',
            'administrator' => '0',
            'contentmanager' => '0',
            'sitemanager' => '0',
          ],
          'reduce' => FALSE,
        ],
        'is_grouped' => FALSE,
        'group_info' => [
          'label' => '',
          'description' => '',
          'identifier' => '',
          'optional' => TRUE,
          'widget' => 'select',
          'multiple' => FALSE,
          'remember' => FALSE,
          'default_group' => 'All',
          'default_group_multiple' => [],
          'group_items' => [],
        ],
        'reduce_duplicates' => FALSE,
        'type' => 'select',
        'limit' => TRUE,
        'vid' => 'social_tagging',
        'hierarchy' => FALSE,
        'error_message' => TRUE,
        'plugin_id' => 'search_api_term',
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialTaggingOverrides';
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
