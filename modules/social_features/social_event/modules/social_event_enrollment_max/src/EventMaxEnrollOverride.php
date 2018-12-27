<?php

namespace Drupal\social_event_max_enroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class EventMaxEnrollOverride.
 *
 * Override event form.
 *
 * @package Drupal\social_event_max_enroll
 */
class EventMaxEnrollOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Add field_event_max_enroll to event form.
    $config_name = 'core.entity_form_display.node.event.default';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      // Add a field group.
      $field_group_settings = $config->get('third_party_settings.field_group');
      $field_group_settings['group_enroll_options'] = [
        'children' => [
          'field_event_max_enroll',
        ],
        'parent_name' => '',
        'weight' => 9,
        'label' => 'Enrollment options',
        'format_type' => 'fieldset',
        'format_settings' => [
          'label' => 'Enrollment options',
          'id' => 'enroll-options',
          'classes' => 'card',
          'required_fields' => FALSE,
        ],
      ];

      // Add the field to the content.
      $content = $config->get('content');
      $content['field_event_max_enroll'] = [
        'weight' => 100,
        'settings' => [
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'type' => 'number',
        'region' => 'content',
      ];

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
    return 'EventMaxEnrollOverride';
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
