<?php

namespace Drupal\social_event_an_enroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class EventAnEnrollOverride.
 *
 * Override event form.
 *
 * @package Drupal\social_event_an_enroll
 */
class EventAnEnrollOverride implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_factory = \Drupal::service('config.factory');

    // Add field_event_an_enroll to event form.
    $config_name = 'core.entity_form_display.node.event.default';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_enrollment_methods.children');
      $children[] = 'field_event_an_enroll';

      $content = $config->get('content');
      $content['field_event_an_enroll'] = [
        'weight' => 100,
        'settings' => [
          'display_label' => TRUE,
        ],
        'third_party_settings' => [],
        'type' => 'boolean_checkbox',
        'region' => 'content',
      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_enrollment_methods' => [
              'children' => $children,
            ],
          ],
        ],
        'content' => $content,
      ];
    }

    $config_name = 'views.view.event_manage_enrollments';
    if (in_array($config_name, $names)) {
      $config = $config_factory->getEditable($config_name);

      $preconfiguration = $config->get('display.default.display_options.fields.views_bulk_operations_bulk_form.preconfiguration.social_event_managers_send_email_action');

      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'fields' => [
                'social_views_bulk_operations_bulk_form_enrollments_1' => [
                  'selected_actions' => [
                    'social_event_an_enroll_send_email_action' => 'social_event_an_enroll_send_email_action',
                  ],
                  'preconfiguration' => [
                    'social_event_an_enroll_send_email_action' => $preconfiguration,
                  ],
                ],
              ],
            ],
          ],
        ],
      ];

      // Unset the regular Email.
      $overrides[$config_name]['display']['default']['display_options']['fields']['social_views_bulk_operations_bulk_form_enrollments_1']['selected_actions']['social_event_managers_send_email_action'] = 0;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'EventAnEnrollOverride';
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
