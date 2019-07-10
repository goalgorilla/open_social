<?php

namespace Drupal\social_follow_content;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialFollowContentConfigOverride.
 *
 * @package Drupal\social_follow_content
 */
class SocialFollowContentConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * TRUE if the "Social Topic" module is enabled.
   *
   * @var bool
   */
  protected $isTopics;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->isTopics = $module_handler->moduleExists('social_topic');
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (!$this->isTopics) {
      return $overrides;
    }

    $config_name = 'field.field.message.create_comment_following_node.field_message_related_object';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'config' => [
            'node.type.topic',
          ] + $this->configFactory->getEditable($config_name)->get('dependencies.config'),
        ],
        'settings' => [
          'node' => [
            'target_bundles' => [
              'topic' => 'topic',
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.following';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'dependencies' => [
          'config' => [
            'node.type.topic',
          ] + $this->configFactory->getEditable($config_name)->get('dependencies.config'),
        ],
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'value' => [
                    'topic' => 'topic',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFollowContentConfigOverride';
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
