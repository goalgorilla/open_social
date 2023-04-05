<?php

namespace Drupal\social_follow_tag;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provide config overrides for the 'social_follow_tag' module.
 *
 * @package Drupal\social_follow_tag
 */
class SocialFollowTagOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Are we in override mode?
   */
  protected bool $inOverride = FALSE;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    // Basically this makes sure we can load overrides without getting stuck
    // in a loop.
    if ($this->inOverride) {
      return [];
    }
    $this->inOverride = TRUE;

    $overrides = [];

    // Add social_tagging taxonomy bundle to follow_term flag config.
    $config_name = 'flag.flag.follow_term';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);
      $bundles = $config->get('bundles');
      if (!empty($bundles) && !in_array('social_tagging', $bundles)) {
        array_push($bundles, 'social_tagging');

        $overrides[$config_name] = [
          'bundles' => $bundles,
        ];
      }
    }

    $config_name = 'field.field.block_content.most_followed_tags.field_terms';
    if (in_array($config_name, $names)) {
      // Get term bundles which already was set in configuration.
      $config = $this->configFactory->getEditable($config_name);
      $bundles = array_values((array) $config->get('settings.handler_settings.target_bundles'));

      // Modify provided bundles to use more or less vocabularies.
      $this->moduleHandler->alter('social_follow_tag_vocabulary_list', $bundles);

      // Set new list of bundles to configurations.
      $target_bundles = [];
      foreach ($bundles as $bundle) {
        $target_bundles[$bundle] = $bundle;
      }
      $overrides[$config_name] = [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => $target_bundles,
          ],
        ],
      ];
    }

    $this->inOverride = FALSE;

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialFollowTagOverrides';
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
