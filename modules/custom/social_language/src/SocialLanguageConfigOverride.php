<?php

namespace Drupal\social_language;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialLanguageConfigOverride.
 *
 * @package Drupal\social_language
 */
class SocialLanguageConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Constructs for SocialGroupSelectorWidgetConfigOverride class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override user form display.
    $config_name = 'core.entity_form_display.user.user.default';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_locale_settings.children');
      $children[] = 'language';

      $content = $config->get('content');
      $content['language'] = [
        'weight' => 1,
        'region' => 'content',
        'settings' => [],
        'third_party_settings' => [],
      ];

      $hidden = $config->get('hidden');
      unset($hidden['language']);

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_locale_settings' => [
              'children' => $children,
            ],
          ],
        ],
        'content' => $content,
        'hidden' => $hidden,
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLanguageConfigOverride';
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
