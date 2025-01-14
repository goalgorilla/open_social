<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "links__language_block" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("links__language_block")
 */
class LanguageLinks extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables): void {
    $variables['attributes']['class'][] = 'dropdown-menu';
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $language = $this->languageManager->getLanguage($langcode);
    if ($language instanceof Language) {
      $variables['heading']['text'] = $language->getName();
    }
  }

}
