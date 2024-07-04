<?php

namespace Drupal\social_group_request;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Social Group request configuration override.
 */
class SocialGroupRequestConfigOverride implements ConfigFactoryOverrideInterface {

  use StringTranslationTrait;

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

    $config_names = [
      'block.block.socialblue_local_tasks',
      'block.block.socialbase_local_tasks',
    ];

    // We only care about our own local tasks,
    // other implementations have the Block UI.
    // Also since it's an optional block, coming from social_core with a
    // dependency on the theme, we can't do this on hook_install as we don't
    // know when social_group_request will be installed and if the block already
    // exists by then.
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        $visibility_paths = $config->get('visibility');
        if (isset($visibility_paths['request_path']['pages'])) {
          $overrides[$config_name] = [
            'visibility' => [
              'request_path' => [
                'pages' => $visibility_paths['request_path']['pages'] . "\r\n/group/*/membership-requests",
              ],
            ],
          ];
        }
      }
    }

    $social_group_types = [];

    $this->moduleHandler->alter('social_group_types', $social_group_types);

    $default_form_display_configs = [];
    $outsider_role_configs = [];
    foreach ($social_group_types as $social_group_type) {
      $default_form_display_configs[] = "core.entity_form_display.group.{$social_group_type}.default";
      $outsider_role_configs[] = "group.role.{$social_group_type}-outsider";
    }

    foreach ($outsider_role_configs as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        $permissions = $config->get('permissions');
        $permissions[] = 'request group membership';

        $overrides[$config_name] = [
          'permissions' => $permissions,
        ];
      }
    }

    foreach ($default_form_display_configs as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'third_party_settings' => [
            'field_group' => [
              'group_request_membership' => [
                'children' => [
                  'allow_request',
                ],
                'parent_name' => '',
                'weight' => 99,
                'label' => $this->t('Request membership')->render(),
                'format_type' => 'fieldset',
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
    return 'SocialGroupRequestConfigOverride';
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
