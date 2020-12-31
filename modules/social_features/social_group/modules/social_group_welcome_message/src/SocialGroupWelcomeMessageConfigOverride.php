<?php

namespace Drupal\social_group_welcome_message;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Social Group welcome message configuration override.
 */
class SocialGroupWelcomeMessageConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $social_group_types = [
      'open_group',
      'closed_group',
      'public_group',
    ];

    $this->moduleHandler->alter('social_group_types', $social_group_types);

    $config_names = [];
    foreach ($social_group_types as $social_group_type) {
      $config_names[] = "core.entity_form_display.group.{$social_group_type}.default";
    }

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'field_group' => [
              'group_welcome_message' => [
                'children' => [
                  'private_message_body',
                  'private_message_send',
                ],
                'parent_name' => 'group_settings',
                'weight' => 1,
                'label' => t('Welcome message')->render(),
                'format_type' => 'details',
                'format_settings' => [
                  'description' => '',
                  'classes' => '',
                  'id' => '',
                  'required_fields' => FALSE,
                ],
              ],
            ],
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGroupWelcomeMessageConfigOverride';
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
