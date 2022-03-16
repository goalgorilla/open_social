<?php

namespace Drupal\social_content_block_landing_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialContentBlockLandingPageConfigOverride.
 *
 * @package Drupal\social_content_block_landing_page
 */
class SocialContentBlockLandingPageConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_name = 'field.field.paragraph.section.field_section_paragraph';

    if (in_array($config_name, $names)) {
      $dependencies = $this->configFactory->getEditable($config_name)
        ->get('dependencies.config');

      $dependencies[] = 'paragraphs.paragraphs_type.custom_content_list';

      $overrides[$config_name] = [
        'dependencies' => [
          'config' => $dependencies,
        ],
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [
              'custom_content_list' => 'custom_content_list',
              'custom_multiple_content_list' => 'custom_multiple_content_list',
            ],
            'target_bundles_drag_drop' => [
              'custom_content_list' => [
                'enabled' => TRUE,
                'weight' => 17,
              ],
              'custom_multiple_content_list' => [
                'enabled' => TRUE,
                'weight' => 18,
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
    return 'SocialContentBlockLandingPageConfigOverride';
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
