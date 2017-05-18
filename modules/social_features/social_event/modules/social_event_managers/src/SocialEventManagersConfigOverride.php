<?php

/**
 * @file
 * Contains \Drupal\social_event_managers\SocialEventManagersConfigOverride.
 */

namespace Drupal\social_event_managers;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class SocialEventManagersConfigOverride implements ConfigFactoryOverrideInterface {

  public function loadOverrides($names) {
    $overrides = array();
    $config_name = 'core.entity_form_display.node.event.default';
    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      // Add a field group.
      $field_group_settings = $config->get('third_party_settings.field_group');
      $field_group_settings['group_event_managers'] = [
        'children' => [
          'field_event_managers',
        ],
        'parent_name' => '',
        'weight' => 9,
        'label' => 'Event managers',
        'format_type' =>  'fieldset',
        'format_settings' => [
          'label' => 'Event managers',
          'id' => 'event-managers',
          'classes' => 'card',
          'required_fields' => FALSE,
        ],
      ];

      // Add the field to the content.
      $content = $config->get('content');
      $content['field_event_managers'] = [];
      $content['field_event_managers']['settings'] = [];
      $content['field_event_managers']['settings']['match_operator'] = 'CONTAINS';
      $content['field_event_managers']['settings']['placeholder'] = '';
      $content['field_event_managers']['settings']['size'] = '60';
      $content['field_event_managers']['type'] = 'entity_reference_autocomplete';
      $content['field_event_managers']['weight'] = 0;
      if (!isset($content['field_event_managers']['third_party_settings'])) {
        $content['field_event_managers']['third_party_settings'] = [];
      }

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => $field_group_settings,
        ],
        'content' => $content,
      ];

    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEventManagersConfigOverride';
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
