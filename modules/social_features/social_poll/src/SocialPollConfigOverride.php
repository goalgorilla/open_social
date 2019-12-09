<?php

namespace Drupal\social_poll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialPollConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_landing_page
 */
class SocialPollConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Whether we use SOLR.
   *
   * TRUE if we use SOLR, FALSE if the database is used.
   *
   * @var bool
   */
  protected $landingPageEnabled;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    // If the `social_landing_page` module is active.
    $this->landingPageEnabled = $module_handler->moduleExists('social_landing_page');
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    // Only add this override if landing page are enabled.
    if (!$this->landingPageEnabled) {
      return [];
    }

    $overrides = [];
    if (in_array('field.field.paragraph.section.field_section_paragraph', $names)) {
      $config_name = 'field.field.paragraph.section.field_section_paragraph';
      // Add our field as config dependency.
      $overrides[$config_name]['dependencies']['config'][] = 'paragraphs.paragraphs_type.poll_item';

      // Add our field itself to the index.
      $overrides[$config_name] = [
        'settings' => [
          'handler_settings' => [
            'target_bundles_drag_drop' => [
              'poll_item' => [
                'enabled' => TRUE,
                'weight' => 9,
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
    return 'SocialPollConfigOverride';
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
