<?php

namespace Drupal\social_content_translation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a base class for configurable content translation config overrides.
 *
 * @package Drupal\social_content_translation
 */
abstract class ContentTranslationConfigOverrideBase implements ConfigFactoryOverrideInterface {

  /**
   * Returns the configuration override for this module's translations.
   *
   * By making this a separate method it can easily be overwritten in child
   * classes without having to duplicate the logic of whether it should be
   * invoked.
   *
   * @return array
   *   An array keyed by configuration name with the override as value.
   */
  abstract protected function getTranslationOverrides();

  /**
   * Returns the module that provides the overrides.
   *
   * This is used as the cache suffix for the overrides.
   *
   * @return string
   *   The module name providing the overrides.
   */
  abstract protected function getModule();

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // This setting can't be changed in an override because that would create
    // and endless loop in trying to apply the override.
    $is_content_translations_enabled = \Drupal::moduleHandler()->moduleExists('social_content_translation');

    if ($is_content_translations_enabled) {
      $translation_overrides = $this->getTranslationOverrides();

      foreach ($translation_overrides as $name => $override) {
        if (in_array($name, $names)) {
          $overrides[$name] = $override;
        }
      }
    }

    return $overrides;
  }

  /**
   * Returns the configurations that are overridden in this class.
   *
   * @return array
   *   An array of configuration names.
   */
  protected function getOverriddenConfigurations() {
    return array_keys($this->getTranslationOverrides());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->getModule() . '.content_translation_defaults_config_override';
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
