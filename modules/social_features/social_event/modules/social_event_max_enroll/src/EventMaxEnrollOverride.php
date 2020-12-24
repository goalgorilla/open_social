<?php

namespace Drupal\social_event_max_enroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\social_event_an_enroll\EventAnEnrollOverride;

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
   * The Social Event AN Enroll config overrider.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollOverride|null
   */
  protected $socialEventAnEnrollOverrider;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollOverride|null $social_event_an_enroll_overrider
   *   The Social Event AN Enroll config overrider.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EventAnEnrollOverride $social_event_an_enroll_overrider = NULL
  ) {
    $this->configFactory = $config_factory;
    $this->socialEventAnEnrollOverrider = $social_event_an_enroll_overrider;
  }

  /**
   * Returns config overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Add field_event_max_enroll to event form.
    $config_name = 'core.entity_form_display.node.event.default';
    if (in_array($config_name, $names)) {
      if ($this->socialEventAnEnrollOverrider instanceof ConfigFactoryOverrideInterface) {
        $parent_overrides = $this->socialEventAnEnrollOverrider->loadOverrides([
          $config_name,
        ]);

        $children = $parent_overrides[$config_name]['third_party_settings']['field_group']['group_enrollment_methods']['children'];
        $content = $parent_overrides[$config_name]['content'];
      }
      else {
        $config = $this->configFactory->getEditable($config_name);
        $children = $config->get('third_party_settings.field_group.group_enrollment_methods.children');
        $content = $config->get('content');
      }

      // Add the field to the content.
      $content['field_event_max_enroll'] = [
        'weight' => 124,
        'settings' => [
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'type' => 'number',
        'region' => 'content',
      ];

      $content['field_event_max_enroll_num'] = [
        'weight' => 125,
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
