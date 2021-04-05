<?php

namespace Drupal\social_profile;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Provide a service for profile tagging.
 *
 * @package Drupal\social_profile
 */
class SocialProfileTagService implements SocialProfileTagServiceInterface {

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * Profile config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $profileConfig;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager
  ) {
    $this->taxonomyStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->profileConfig = $config_factory->get('social_profile.settings');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->profileConfig->get('enable_profile_tagging');
  }

  /**
   * {@inheritdoc}
   */
  public function hasContent() {
    if (count($this->getCategories()) == 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function allowSplit() {
    return $this->isActive() && $this->profileConfig->get('allow_category_split');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Define as array.
    $options = [];

    // Get the site's current language.
    $current_lang = $this->languageManager->getCurrentLanguage()->getId();

    // Fetch main categories.
    // If the website is multilingual, we want to first check for the terms
    // in current language. At the moment, users do not add proper language to
    // vocabulary terms which may result in return of empty array on loadTree()
    // function. So, we want to check for the terms also in default language if
    // we don't find terms in current language.
    if (!empty($current_lang_terms = $this->taxonomyStorage
      ->loadTree('profile_tag', 0, 1, FALSE, $current_lang))) {
      $options = $this->prepareTermOptions($current_lang_terms);
    }
    // Add a fallback to default language of the website if the current
    // language has no terms.
    elseif (!empty($default_lang_terms = $this->taxonomyStorage
      ->loadTree('profile_tag', 0, 1, FALSE))) {
      $options = $this->prepareTermOptions($default_lang_terms);
    }

    // Return array.
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrens($category) {
    // Define as array.
    $options = [];

    // Get the site's current language.
    $current_lang = $this->languageManager->getCurrentLanguage()->getId();

    if (!empty($current_lang_terms = $this->taxonomyStorage
      ->loadTree('profile_tag', $category, 1, FALSE, $current_lang))) {
      $options = $this->prepareTermOptions($current_lang_terms);
    }
    // Add a fallback to default language of the website if the current
    // language has no terms.
    elseif (!empty($default_lang_terms = $this->taxonomyStorage
      ->loadTree('profile_tag', $category, 1, FALSE))) {
      $options = $this->prepareTermOptions($default_lang_terms);
    }

    // Return array.
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function useCategoryParent() {
    return $this->profileConfig->get('use_category_parent');
  }

  /**
   * {@inheritdoc}
   */
  public function tagLabelToMachineName($label) {
    return strtolower(str_replace(' ', '', $label));
  }

  /**
   * {@inheritdoc}
   */
  public function buildHierarchy(array $term_ids) {
    $tree = [];
    $terms = $this->taxonomyStorage->loadMultiple(array_column($term_ids, 'target_id'));
    if (empty($terms)) {
      return [];
    }

    foreach ($terms as $term) {
      if (!$term instanceof TermInterface) {
        continue;
      }

      $parents = $this->taxonomyStorage->loadParents($term->id());
      if ($parents) {
        $parent = reset($parents);
      }
      else {
        $parent = $term;
      }
      $parent_label = $parent->getName();
      $route = 'view.search_users.page_no_value';
      $route_parameters = [
        'created_op' => '<',
        'profile_tag[]' => $term->id(),
      ];

      // Prepare the URL for the search by term.
      $url = Url::fromRoute($route, $route_parameters)->toString();

      $tree[$parent->id()]['title'] = $parent_label;
      $tree[$parent->id()]['tags'][$term->id()] = [
        'url' => $url,
        'name' => $term->getName(),
      ];
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermOptionNames(array $term_ids) {
    $options = [];
    if (empty($term_ids)) {
      return $options;
    }

    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = $this->taxonomyStorage->loadMultiple($term_ids);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    return $options;
  }

  /**
   * Helper function to prepare term options.
   *
   * @param array $terms
   *   Array of terms.
   *
   * @return array
   *   Returns a list of terms options.
   */
  private function prepareTermOptions(array $terms) {
    $options = [];
    foreach ($terms as $category) {
      $options[$category->tid] = $category->name;
    }

    return $options;
  }

}
