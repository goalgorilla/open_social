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
      $children = $config->get('third_party_settings.field_group.group_enrollment.children');
      $children[] = 'field_event_max_enroll';
      $children[] = 'field_event_max_enroll_num';

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

      $content['field_event_max_enroll_num'] = [
        'weight' => 101,
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
            'group_enrollment' => [
              'children' => $children,
            ],
          ],
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
