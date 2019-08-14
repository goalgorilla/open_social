<?php

namespace Drupal\social_group_request;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Social Group request configuration override.
 */
class SocialGroupRequestConfigOverride implements ConfigFactoryOverrideInterface {

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
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach ($names as $name) {
      if (strpos($name, 'block.block.') === 0) {
        $config = $this->configFactory->getEditable($name);

        if ($config->get('settings.id') == 'group_hero_block') {
          $visibility_paths = $config->get('visibility.request_path.pages');

          $overrides[$name] = [
            'visibility' => [
              'request_path' => [
                'pages' => $visibility_paths . "\r\n/group/*/members-pending",
              ],
            ],
          ];
        }
      }
    }

    $config_name = 'core.entity_form_display.group.closed_group.default';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);
      $dependencies_config = $config->get('dependencies.config');
      $dependencies_config[] = 'field.field.group.closed_group.field_group_allow_request';

      $group_content_children = $config->get('third_party_settings.field_group.group_content.children');
      $group_content_children[] = 'field_group_allow_request';

      $overrides[$config_name] = [
        'dependencies' => [
          'config' => $dependencies_config,
        ],
        'third_party_settings' => [
          'field_group' => [
            'group_content' => [
              'children' => $group_content_children,
            ],
          ],
        ],
        'content' => [
          'field_group_allow_request' => [
            'weight' => 1,
            'settings' => [
              'display_label' => TRUE,
            ],
            'third_party_settings' => [],
            'type' => 'boolean_checkbox',
            'region' => 'content',
          ],
        ],
      ];
    }

    $config_name = 'group.role.closed_group-outsider';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);
      $permissions = $config->get('permissions');
      $permissions[] = 'request group membership';

      $overrides[$config_name] = [
        'permissions' => $permissions,
      ];
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
