<?php

namespace Drupal\social_core;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a base class for configurable content translation config overrides.
 *
 * @package Drupal\social_core
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
   * This is used as the social_contant_translation.settings configuration key
   * as well as in the cache suffix for the overrides.
   *
   * @return string
   *   The module name providing the overrides.
   */
  abstract protected function getModule();

  /**
   * Returns the display name for this set of configuration overrides.
   *
   * This can be used in a user interface to let sitemanagers determine which
   * parts of Open Social should be translatable. For consistency when
   * displaying this should always be a plural string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The (translatable) string that can be shown to the user.
   */
  abstract protected function getDisplayName();

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // This setting can't be changed in an override because that would create
    // and endless loop in trying to apply the override.
    $settings = \Drupal::configFactory()->getEditable('social_content_translation.settings');
    $is_enabled = $settings->getOriginal($this->getModule(), FALSE);

    if ($is_enabled) {
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
    $metadata = new CacheableMetadata();
    if (in_array($name, $this->getOverriddenConfigurations())) {
      $metadata->addCacheTags(['config:social_content_translation.settings']);
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
