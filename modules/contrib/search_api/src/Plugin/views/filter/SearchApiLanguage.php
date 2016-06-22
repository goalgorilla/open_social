<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\search_api\UncacheableDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a filter for filtering on the language of items.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_language")
 */
class SearchApiLanguage extends SearchApiOptions {

  use UncacheableDependencyTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|null
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var $language_manager \Drupal\Core\Language\LanguageManagerInterface */
    $language_manager = $container->get('language_manager');
    $plugin->setLanguageManager($language_manager);

    return $plugin;
  }

  /**
   * Retrieves the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function getLanguageManager() {
    return $this->languageManager ?: \Drupal::languageManager();
  }

  /**
   * Sets the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The new language manager.
   *
   * @return $this
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = array(
      'content' => $this->t('Current content language'),
      'interface' => $this->t('Current interface language'),
      'default' => $this->t('Default site language'),
    );

    foreach ($this->getLanguageManager()->getLanguages(LanguageInterface::STATE_ALL) as $langcode => $language) {
      $this->valueOptions[$langcode] = $language->getName();
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    foreach ($this->value as $i => $value) {
      if ($value == 'content') {
        $this->value[$i] = $this->getLanguageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      }
      elseif ($value == 'interface') {
        $this->value[$i] = $this->getLanguageManager()->getCurrentLanguage()->getId();
      }
      elseif ($value == 'default') {
        $this->value[$i] = $this->getLanguageManager()->getDefaultLanguage()->getId();
      }
    }

    parent::query();
  }

}
