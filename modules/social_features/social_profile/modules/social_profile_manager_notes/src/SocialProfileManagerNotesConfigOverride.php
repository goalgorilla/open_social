<?php

namespace Drupal\social_profile_manager_notes;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialProfileManagerNotesConfigOverride.
 *
 * Example configuration override.
 *
 * @package Drupal\social_profile_manager_notes
 */
class SocialProfileManagerNotesConfigOverride implements ConfigFactoryOverrideInterface {

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

    $config_names = [
      'core.entity_form_display.profile.profile.default',
      'core.entity_view_display.profile.profile.default'
    ];

    foreach($config_names as $config_name) {
      if (in_array($config_name, $names)) {

        // Grab current configuration and push the new values.
        $config = $this->configFactory->getEditable($config_name);
        // We have to add config dependencies to field storage.
        $dependencies = $config->getOriginal('dependencies', FALSE)['config'];
        $dependencies[] = 'field.field.profile.profile.field_note';
        $overrides[$config_name]['dependencies']['config'] = $dependencies;

        // Add the manager note field to the profile.
        $overrides[$config_name]['content']['field_note'] = [
          'weight' => 6,
          'settings' => [
            'view_mode' => 'default',
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'type' => 'comment_default',
          'region' => 'content',
          'label' => 'visually_hidden',
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialProfileManagerNotesConfigOverride';
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

