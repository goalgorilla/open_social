<?php

namespace Drupal\social_tagging;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configuration override.
 */
class SocialTaggingOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The tagging helper service.
   *
   * @var \Drupal\social_tagging\SocialTaggingService
   */
  protected $tagService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\social_tagging\SocialTaggingService $tag_service
   *   The tagging helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(SocialTaggingService $tag_service, ConfigFactoryInterface $config_factory) {
    $this->tagService = $tag_service;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'views.view.search_content',
      'views.view.search_groups',
      'search_api.index.social_content',
      'search_api.index.social_groups',
      'views.view.latest_topics',
      'views.view.upcoming_events',
      'views.view.topics',
      'views.view.events',
      'views.view.group_topics',
      'views.view.group_events',
      'views.view.newest_groups',
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

    // Check if tagging is active.
    if (!($this->tagService->active() && $this->tagService->hasContent())) {
      return $overrides;
    }

    // Add tagging field to the search index.
    $config_search = [
      'search_api.index.social_content' => 'node',
    ];
    if ($this->tagService->groupActive()) {
      $config_search['search_api.index.social_groups'] = 'group';
    }

    foreach ($config_search as $config_name => $type) {
      if (in_array($config_name, $names)) {
        $field_settings = $this->configFactory->getEditable($config_name)
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

    if ($this->tagService->allowSplit()) {
      $fields = [];
      foreach ($this->tagService->getCategories() as $tid => $value) {
        if (!empty($this->tagService->getChildren($tid))) {
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
    if ($this->tagService->groupActive()) {
      $config_views['views.view.search_groups'] = 'search_api_index_social_groups';
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

    if ($this->tagService->allowSplit()) {
      $fields = [];
      foreach ($this->tagService->getCategories() as $tid => $value) {
        if (!empty($this->tagService->getChildren($tid))) {
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
