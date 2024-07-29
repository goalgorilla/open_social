<?php

namespace Drupal\social_language\PathProcessor;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\path_alias\AliasManagerInterface;
use \Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides "AliasPathProcessor" path processor to prevent "404" exceptions.
 */
class SocialLanguagePathProcessor extends AliasPathProcessor {

  /**
   * Language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a SocialLanguagePathProcessor object.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(AliasManagerInterface $alias_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($alias_manager);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    $possible_alias = $path;
    $path = parent::processInbound($possible_alias, $request);

    if ($path !== $possible_alias) {
      return $path;
    }

    if (!$this->languageManager->isMultilingual()) {
      return $path;
    }

    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    if ($langcode === $default_langcode) {
      return $path;
    }

    // Case: when we have a topic in "English" version but a user has "Dutch"
    // and try to visit the topic the result will be "Page not found".
    // There we're trying to find path for alias in default language and
    // prevent 404 exception.
    return $this->aliasManager->getPathByAlias($path, $default_langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $alias = parent::processOutbound($path, $options, $request, $bubbleable_metadata);
    if ($path !== $alias) {
      return $alias;
    }

    if (!$this->languageManager->isMultilingual()) {
      return $alias;
    }

    $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    if ($langcode === $default_langcode) {
      return $alias;
    }

    if (empty($options['alias'])) {
      $alias = $this->aliasManager->getAliasByPath($path, $default_langcode);
      // Ensure the resulting path has at most one leading slash, to prevent it
      // becoming an external URL without a protocol like //example.com. This
      // is done in \Drupal\Core\Routing\UrlGenerator::generateFromRoute()
      // also, to protect against this problem in arbitrary path processors,
      // but it is duplicated here to protect any other URL generation code
      // that might call this method separately.
      if (str_starts_with($alias, '//')) {
        $alias = '/' . ltrim($alias, '/');
      }
    }

    return $alias;
  }

}
