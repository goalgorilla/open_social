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
   * Whether this config override should apply to the provided configurations.
   *
   * Used to check what to return in loadOverrides as well as in the metadata
   * method.
   *
   * @param array $names
   *   The names of the configs for which overrides are being loaded.
   *
   * @return bool
   *   Whether we override those configs.
   */
  private function shouldApplyOverrides(array $names) {
    $config_names = [
      'views.view.search_content',
      'views.view.search_groups',
      'views.view.search_users',
      'search_api.index.social_content',
      'search_api.index.social_groups',
      'search_api.index.social_users',
      'views.view.latest_topics',
      'views.view.upcoming_events',
      'views.view.topics',
      'views.view.events',
      'views.view.group_topics',
      'views.view.group_events',
      'views.view.newest_groups',
      'core.entity_view_display.profile.profile.default',
    ];

    // We loop over the provided names instead of the config names we override
    // which is slightly faster in the case of being called from
    // `getCacheableMetadata` which checks for a single config only.
    foreach ($names as $name) {
      if (in_array($name, $config_names)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if (!$this->shouldApplyOverrides($names)) {
      return [];
    }

    $overrides = [];

    /** @var \Drupal\social_tagging\SocialTaggingService $tag_service */
    $tag_service = \Drupal::service('social_tagging.tag_service');

    // Check if tagging is active.
    if (!($tag_service->active() && $tag_service->hasContent())) {
      return $overrides;
    }

    // Add tagging field to the search index.
    $config_search = [
      'search_api.index.social_content' => 'node',
    ];
    if ($tag_service->groupActive()) {
      $config_search['search_api.index.social_groups'] = 'group';
    }
    if ($tag_service->profileActive()) {
      $config_search['search_api.index.social_users'] = 'profile';
    }

    foreach ($config_search as $config_name => $type) {
      if (in_array($config_name, $names)) {
        $field_settings = \Drupal::configFactory()->getEditable($config_name)
          ->get('field_settings');

        $field_settings['social_tagging'] = [
          'label' => 'Tags',
          'datasource_id' => 'entity:' . $type,
          'property_path' => 'social_tagging',
          'type' => 'integer',
        ];
        $overrides[$config_name]['field_settings'] = $field_settings;
      }
    }

    // Prepare fields.
    $fields['social_tagging'] = [
      'identifier' => 'tag',
      'label' => 'Tags',
    ];

    if ($tag_service->allowSplit()) {
      $fields = [];
      foreach ($tag_service->getCategories() as $tid => $value) {
        if (!empty($tag_service->getChildren($tid))) {
          $fields['social_tagging_' . $tid] = [
            'identifier' => social_tagging_to_machine_name($value),
            'label' => $value,
          ];
        }
        // Display parent of tags.
        elseif ($tag_service->useCategoryParent()) {
          $fields['social_tagging_' . $tid] = [
            'identifier' => social_tagging_to_machine_name($value),
            'label' => $value,
          ];
        }
      }
    }

    // Add tagging fields to the views filters.
    $config_views = [
      'views.view.search_content' => 'search_api_index_social_content',
    ];
    if ($tag_service->groupActive()) {
      $config_views['views.view.search_groups'] = 'search_api_index_social_groups';
    }
    if ($tag_service->profileActive()) {
      $config_views['views.view.search_users'] = 'search_api_index_social_users';
    }

    foreach ($config_views as $config_name => $type) {
      if (in_array($config_name, $names)) {

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
            'table' => $type,
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

      }
    }

    // Prepare fields.
    $fields = [];
    $fields['social_tagging_target_id'] = [
      'identifier' => 'tag',
      'label' => 'Tags',
    ];

    if ($tag_service->allowSplit()) {
      $fields = [];
      foreach ($tag_service->getCategories() as $tid => $value) {
        if (!empty($tag_service->getChildren($tid))) {
          $fields['social_tagging_target_id_' . $tid] = [
            'identifier' => social_tagging_to_machine_name($value),
            'label' => $value,
          ];
        }
      }
    }

    // Add tagging fields to the views filters.
    $config_overviews = [
      'views.view.latest_topics' => 'page_latest_topics',
      'views.view.upcoming_events' => 'page_community_events',
      'views.view.topics' => 'default',
      'views.view.events' => 'events_overview',
      'views.view.group_topics' => 'default',
      'views.view.group_events' => 'default',
      'views.view.newest_groups' => 'page_all_groups',
    ];

    foreach ($config_overviews as $config_name => $display) {
      if (in_array($config_name, $names)) {

        $overrides[$config_name]['dependencies']['config'][] = 'taxonomy.vocabulary.social_tagging';
        $overrides[$config_name]['display'][$display]['cache_metadata']['contexts'][] = 'user';

        $group = 1;

        if (count($fields) > 1) {
          $overrides[$config_name]['display'][$display]['display_options']['filter_groups']['groups'][1] = 'AND';
          $overrides[$config_name]['display'][$display]['display_options']['filter_groups']['groups'][2] = 'OR';
          $group++;
        }

        $relationship = ($config_name === 'views.view.group_topics' || $config_name === 'views.view.group_events') ? 'gc__node' : 'none';
        $table = ($config_name === 'views.view.newest_groups') ? 'group__social_tagging' : 'node__social_tagging';

        foreach ($fields as $field => $data) {
          $overrides[$config_name]['display'][$display]['display_options']['filters'][$field] = [
            'id' => $field,
            'table' => $table,
            'field' => 'social_tagging_target_id',
            'relationship' => $relationship,
            'group_type' => 'group',
            'admin_label' => '',
            'operator' => '=',
            'value' => [
              'min' => '',
              'max' => '',
              'value' => '',
            ],
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
              'multiple' => FALSE,
              'remember_roles' => [
                'authenticated' => 'authenticated',
                'anonymous' => '0',
                'administrator' => '0',
                'contentmanager' => '0',
                'sitemanager' => '0',
              ],
              'placeholder' => '',
              'min_placeholder' => '',
              'max_placeholder' => '',
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
            'entity_type' => 'node',
            'entity_field' => 'social_tagging',
            'plugin_id' => 'numeric',
          ];
        }

      }
    }

    $config_name = 'core.entity_view_display.profile.profile.default';
    if (in_array($config_name, $names)) {
      $overrides[$config_name]['content']['social_tagging'] = [
        'weight' => 7,
        'label' => 'visually_hidden',
        'settings' => [
          'link' => 'true',
        ],
        'third_party_settings' => [],
        'type' => 'entity_reference_label',
        'region' => 'content',
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
    // If this override doesn't apply to the given config then we return an
    // empty cacheable metadata object that can be cached forever.
    if (!$this->shouldApplyOverrides([$name])) {
      return new CacheableMetadata();
    }

    // We could distinguish between the various configurations that we override
    // in how granular we cache. However, for simplicity just cache based on all
    // the calls that are made in the loadOverrides method and assume this is
    // the same for any config that's overridden.
    $metadata = new CacheableMetadata();
    // Calls to SocialTaggingService's methods active, groupActive, allowSplit
    // all depend on the settings under the hood.
    $metadata->addCacheTags(['config:social_tagging.settings']);
    // The loadOverrides method calls the getCategories and getChildren methods
    // to build the fields that are shown in the views and the values they have.
    // The output of these methods ultimately depends on the contents of the
    // `social_tagging` vocabulary. With that in mind we want to invalidate on
    // any taxonomy change in the `social_tagging` vocabulary which we can do
    // with the `taxonomy_term_list` cache tag that's automatically defined as
    // EntityType::id . '_list_' in EntityType::__construct. Additionally as of
    // 8.9.0 (https://www.drupal.org/node/3107058) we can specify a specific
    // Taxonomy bundle. (Remember that a taxonomy Term bundle is its
    // vocabulary).
    // So for Drupal versions before 8.9.0 we'll have to invalidate on any term
    // addition and for anything above 8.9.0 we can enjoy a performance
    // increase.
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      $metadata->addCacheTags(['taxonomy_term_list']);
    }
    else {
      $metadata->addCacheTags(['taxonomy_term_list:social_tagging']);
    }

    // The above should ensure that if things change in the tagging
    // configuration or the available tags, the search UI is rebuilt.
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
