<?php

namespace Drupal\social_core;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
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
   * This is used as the social_content_translation.settings configuration key
   * as well as in the cache suffix for the overrides.
   *
   * @return string
   *   The module name providing the overrides.
   */
  abstract protected function getModule();

  /**
   * Returns the display name for this set of configuration overrides.
   *
   * This can be used in a user interface to let site managers determine which
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

    // There are some performance issues, even when content translation is
    // disabled.
    // Since we check for the social_content_translation settings, if the
    // module is configured and enabled for content translation.
    // We can easily skip if the module isn't enabled.
    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    $is_content_translations_enabled = \Drupal::moduleHandler()
      ->moduleExists('social_content_translation');

    if (!$is_content_translations_enabled) {
      return $overrides;
    }

    // If the module is enabled, we only need to get the content translation
    // configuration once per request. To see for what it's enabled.
    // This setting can't be changed in an override because that would create
    // and endless loop in trying to apply the override.
    $is_enabled = $this->isContentTranslationEnabledForModule();
    if (!$is_enabled) {
      return $overrides;
    }
    else {
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
   * Returns the Content translations settings.
   *
   * @return \Drupal\Core\Config\Config
   *   The Config for content translation settings.
   */
  protected function getContentTranslationSettings(): Config {
    $settings = &drupal_static(__FUNCTION__);

    // Let's statically cache this, because of performance reasons
    // this could get called quite often (for all the loadOverrides).
    if (empty($settings)) {
      $settings = \Drupal::configFactory()->getEditable('social_content_translation.settings');
    }

    return $settings;
  }

  /**
   * Checks for the given Module if we configured content translations.
   *
   * @return mixed
   *   True if it's enabled for the current checked module or null.
   */
  protected function isContentTranslationEnabledForModule(): mixed {
    $settings = $this->getContentTranslationSettings();
    return $settings->getOriginal($this->getModule(), FALSE);
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}
