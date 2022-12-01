<?php

namespace Drupal\social_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom tagging service.
 */
class SocialTaggingService {

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injection of the entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Injection of the languageManager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->languageManager = $language_manager;
  }

  /**
   * Returns whether the feature is turned on or not.
   *
   * @return bool
   *   Whether tagging is turned on or not.
   */
  public function active() {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('enable_content_tagging');
  }

  /**
   * Returns whether the feature is turned on for groups or not.
   *
   * @return bool
   *   Whether tagging is turned on or not for groups.
   */
  public function groupActive() {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('tag_type_group');
  }

  /**
   * Returns whether the feature is turned on for profiles or not.
   *
   * @return bool
   *   Whether tagging is turned on or not for profiles.
   */
  public function profileActive() {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('tag_type_profile');
  }

  /**
   * Returns if there are any taxonomy items available.
   *
   * @return bool
   *   If there are tags available.
   */
  public function hasContent() {

    if (count($this->getCategories()) == 0) {
      return FALSE;
    }

    if (count($this->getAllChildren()) == 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns whether splitting of fields is allowed.
   *
   * @return bool
   *   Whether category split on field level is turned on or not.
   */
  public function allowSplit() {
    return (bool) ($this->active() && $this->configFactory->get('social_tagging.settings')->get('allow_category_split'));
  }

  /**
   * Returns the filter query condition.
   *
   * @return string
   *   Returns OR or AND.
   */
  public function queryCondition() {
    return (string) ($this->configFactory->get('social_tagging.settings')->get('use_and_condition') ? 'AND' : 'OR');
  }

  /**
   * Returns whether using a parent of categories is allowed.
   *
   * @return bool
   *   Whether using categories parent is turned on or not..
   */
  public function useCategoryParent() {
    return (bool) ($this->active() && $this->configFactory->get('social_tagging.settings')->get('use_category_parent'));
  }

  /**
   * Returns all the top level term items, that are considered categories.
   *
   * @return array
   *   An array of top level category items.
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
    if (!empty($current_lang_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('social_tagging', 0, 1, FALSE, $current_lang))) {
      $options = $this->prepareTermOptions($current_lang_terms);
    }
    // Add a fallback to default language of the website if the current
    // language has no terms.
    elseif (!empty($default_lang_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('social_tagging', 0, 1, FALSE))) {
      $options = $this->prepareTermOptions($default_lang_terms);
    }

    // Return array.
    return $options;
  }

  /**
   * Returns the children of top level term items.
   *
   * @param int $category
   *   The category you want to fetch the child items from.
   *
   * @return array
   *   An array of child items.
   */
  public function getChildren($category) {
    // Define as array.
    $options = [];

    // Get the site's current language.
    $current_lang = $this->languageManager->getCurrentLanguage()->getId();

    if (!empty($current_lang_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('social_tagging', $category, 1, FALSE, $current_lang))) {
      $options = $this->prepareTermOptions($current_lang_terms);
    }
    // Add a fallback to default language of the website if the current
    // language has no terms.
    elseif (!empty($default_lang_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('social_tagging', $category, 1, FALSE))) {
      $options = $this->prepareTermOptions($default_lang_terms);
    }

    // Return array.
    return $options;
  }

  /**
   * Returns all the children of top level term items.
   *
   * @return array
   *   An array of child items.
   */
  public function getAllChildren() {
    // Define as array.
    $options = [];

    // Fetch main categories.
    foreach (array_keys($this->getCategories()) as $category) {
      $options = array_merge($options, $this->getChildren($category));
    }
    // Return array.
    return $options;
  }

  /**
   * Returns a multilevel tree.
   *
   * @param array $term_ids
   *   An array of items that are selected.
   * @param string $entity_type
   *   The entity type these tags are for.
   *
   * @return array
   *   An hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $term_ids, $entity_type) {
    $tree = [];
    // Load all the terms together.
    if (!empty($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple(array_column($term_ids, 'target_id')))) {
      // Get current language.
      // This is used to get the translated term, if available.
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      // Get splitting of fields option.
      $allowSplit = $this->allowSplit();

      // Set the route.
      $route = ($entity_type == 'group') ? 'view.search_groups.page_no_value' : 'view.search_content.page_no_value';
      if ($entity_type == 'profile') {
        $route = 'view.search_users.page_no_value';
      }

      // Build the hierarchy.
      foreach ($terms as $current_term) {
        // Must be a valid Term.
        if (
          !$current_term instanceof TermInterface ||
          !$current_term->isPublished()
        ) {
          continue;
        }
        // Get current terms parents.
        if ($parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadParents($current_term->id())) {
          /** @var \Drupal\taxonomy\Entity\Term $parent */
          $parent = reset($parents);
          if ($parent->hasTranslation($langcode)) {
            /** @var \Drupal\taxonomy\Entity\Term $translated_term */
            $translated_term = $parent->getTranslation($langcode);
            $category_label = $translated_term->getName();
          }
          else {
            $category_label = $parent->getName();
          }
        }
        // Or add the parent term itself if it connected to the content.
        else {
          if ($current_term->hasTranslation($langcode)) {
            /** @var \Drupal\taxonomy\Entity\Term $translated_term */
            $translated_term = $current_term->getTranslation($langcode);
            $category_label = $translated_term->getName();
          }
          else {
            $category_label = $current_term->getName();
          }
          $parent = $current_term;
        }
        // Prepare the parameter;.
        // @todo Replace with dependency injection in Open Social 12.0.0.
        $parameter = $allowSplit ? \Drupal::service('social_core.machine_name')->transform($category_label) : 'tag';

        $route_parameters = [
          $parameter . '[]' => $current_term->id(),
        ];
        if ($entity_type == 'profile') {
          $route_parameters['created_op'] = '<';
        }

        // Prepare the URL for the search by term.
        $url = Url::fromRoute($route, $route_parameters)->toString();

        // Finally, prepare the hierarchy.
        $tree[$parent->id()]['title'] = $category_label;

        if ($current_term->hasTranslation($langcode)) {
          /** @var \Drupal\taxonomy\Entity\Term $translated_term */
          $translated_term = $current_term->getTranslation($langcode);
          $term_name = $translated_term->getName();
        }
        else {
          $term_name = $current_term->getName();
        }

        $tree[$parent->id()]['tags'][$current_term->id()] = [
          'url' => $url,
          'name' => $term_name,
        ];
      }
    }

    // Return the tree.
    return $tree;
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
      if ((bool) $category->status) {
        $options[$category->tid] = $category->name;
      }
    }

    return $options;
  }

}
