<?php

namespace Drupal\social_core;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a trait to fix the URL generation for single-language entities.
 *
 * In Open Social it's possible to have interface translations enabled while
 * having entities that are only available in the default language. By default,
 * when such entities are rendered, Drupal will link to the entity's default
 * language. This can cause the interface translation language to change for the
 * user.
 *
 * This trait implements the EntityInterface::toUrl method to ensure links
 * don't change the interface language of the current user, even if the entity
 * does not have a translation available.
 *
 * This Trait should only be used on classes extending EntityBase.
 */
trait EntityUrlLanguageTrait {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $url = parent::toUrl($rel, $options);

    // If a language was requested explicitly then it's not overwritten.
    // e.g. this happens on content translations overview pages for links to the
    // specific translations.
    if (isset($options['language'])) {
      return $url;
    }

    $url_options = $url->getOptions();

    // Only override the language if the parent `toUrl` method specified a
    // language. This avoids accidentally setting a language for a page that is
    // not tied to the entity's language.
    if (isset($url_options['language'])) {
      // Override the language to keep the user in the current content language.
      // By default Drupal sets the link language to the Entity's language which
      // would cause the content language for the current user to change, this
      // is undesired. Setting the language to the current content user relies
      // on the fact that Drupal displays the default language translation for
      // the entity when no translation is available but properly adjusts links.
      $url_options['language'] = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $url->setOptions($url_options);
    }

    return $url;
  }

}
