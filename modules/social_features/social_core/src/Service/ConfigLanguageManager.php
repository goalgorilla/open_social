<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class ConfigLanguageManager.
 *
 * @package Drupal\social_core\Service
 *
 * Usage:
 * 1. configOverrideLanguageStart($langcode)
 * 2. Load configuration in correct language
 * 3. configOverrideLanguageEnd()
 *
 * Explanation on example why this is needed:
 *
 *    Accessing translated configuration
 *
 *    Drupal by default always uses the language selected for the page to load
 *    configuration with. So if you are viewing a Spanish page, all
 *    configuration is loaded with Spanish translations merged in. If you are
 *    viewing the same page in Hungarian, the same code will now receive
 *    Hungarian translated configuration. This means normally you don't need to
 *    do anything special to access configuration in the language needed.
 *
 *    However, there are cases, when you want to load the original copy of the
 *    configuration or ask for a specific language. Such as when sending emails
 *    to users, you will need configuration values in the right language.
 *    The following code is a slightly adapted excerpt from user_mail() to
 *    illustrate loading configuration in the preferred language of $account to
 *    compose an email:
 *
 * @code
 *
 *    <?php
 *      $language_manager = \Drupal::languageManager();
 *      $language = $language_manager->getLanguage($account->getPreferredLangcode());
 *      $original_language = $language_manager->getConfigOverrideLanguage();
 *      $language_manager->setConfigOverrideLanguage($language);
 *      $mail_config = \Drupal::config('user.mail');
 *        // ...
 *        // ...Compose (and send) email here...
 *        // ...
 *      $language_manager->setConfigOverrideLanguage($original_language);
 *    ?>
 *
 * @endcode
 *
 *    Note that you are setting values on the language manager and not the
 *    configuration system directly. The configuration system is
 *    override-agnostic and can support overrides of different kinds with
 *    various conditions. It is the job of the overrides to manage their
 *    conditions, in this case to allow changing the language used.
 *    The same pattern can be used to load configuration entities in specific
 *    languages as well.
 *
 *    Source: https://www.hojtsy.hu/blog/2014-may-26/drupal-8-multilingual-tidbits-16-configuration-translation-development
 */
class ConfigLanguageManager {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Configuration overrode language in which configuration should be processed.
   *
   * @var \Drupal\Core\Language\LanguageInterface|null
   */
  protected ?LanguageInterface $translationLanguage;

  /**
   * Original configuration overrode language.
   *
   * This variable is used to store original language, so it can be reverted.
   *
   * @var \Drupal\Core\Language\LanguageInterface|null
   */
  protected ?LanguageInterface $originalLanguage;

  /**
   * ConfigLanguageManager constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * Define language in which configuration should be processed.
   *
   * @param string $langcode
   *   The language code.
   */
  public function configOverrideLanguageStart(string $langcode): void {
    if (empty($this->translationLanguage = $this->languageManager->getLanguage($langcode))) {
      $this->translationLanguage = $this->languageManager->getCurrentLanguage();
    }
    $this->originalLanguage = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($this->translationLanguage);
  }

  /**
   * Revert to original language defined by configOverrideLanguageStart().
   */
  public function configOverrideLanguageEnd(): void {
    if ($this->originalLanguage) {
      $this->languageManager->setConfigOverrideLanguage($this->originalLanguage);
    }
    else {
      throw new \InvalidArgumentException('configOverrideLanguageStart($langcode) method must be called before configOverrideLanguageEnd().');
    }

  }

}
