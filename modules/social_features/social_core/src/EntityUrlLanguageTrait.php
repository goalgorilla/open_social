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
 * doesn't change the interface language of the current user, even if the entity
 * does not have a translation available.
 */
trait EntityUrlLanguageTrait {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $url = parent::toUrl($rel, $options);

    // If a language was requested explicitly then it's not overwritten.
    // e.g. this happens on content translations.
    if (isset($options['language'])) {
      return $url;
    }

    // Override the language to keep the user in the current content language.
    // Posts in Open Social are not translatable but by default. However,
    // Drupal sets the link language to the Entity's language which would cause
    // the content language for the current user to change, this is undesired.
    $url_options = $url->getOptions();
    if (isset($url_options['language'])) {
      // The link language is only changed if it would cause the language for
      // the viewing user to be changed. This ensures that extending platforms
      // can make posts translatable without issues.
      // TODO: Implement logic here.
      // Check if post has translation in content language.
      $url_options['language'] = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $url->setOptions($url_options);
    }

    return $url;
  }

}
