<?php

namespace Drupal\social_language;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

/**
 * Class SocialLanguageMetadataBubblingUrlGenerator.
 *
 * @package Drupal\social_language
 */
class SocialLanguageMetadataBubblingUrlGenerator extends MetadataBubblingUrlGenerator {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new bubbling URL generator service.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The non-bubbling URL generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(UrlGeneratorInterface $url_generator, RendererInterface $renderer, LanguageManagerInterface $language_manager) {
    parent::__construct($url_generator, $renderer);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
    if (isset($options['language']) && $options['language'] instanceof LanguageInterface) {
      $language = $this->languageManager->getCurrentLanguage();

      if ($options['language']->getId() != $language->getId()) {
        $reset_language = TRUE;
        $unmodified_pages = [
          'content_translation_overview',
        ];
        $current_route = \Drupal::routeMatch()->getRouteName();
        \Drupal::moduleHandler()
          ->alter('social_language_unmodified_pages', $unmodified_pages);
        $route_parts = explode('.', $current_route);
        foreach ($unmodified_pages as $page) {
          if (in_array($page, $route_parts)) {
            $reset_language = FALSE;
            break;
          }
        }
        if ($reset_language) {
          $options['language'] = $language;
        }
      }
    }

    return parent::generateFromRoute($name, $parameters, $options, $collect_bubbleable_metadata);
  }

}
