<?php

namespace Drupal\social_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom tagging service.
 */
class SocialTaggingService {

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injection of the entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->configFactory = $configFactory;
  }

  /**
   * Returns wether the feature is turned on or not.
   *
   * @return bool
   *   Wether tagging is turnded on or not.
   */
  public function active() {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('enable_content_tagging');
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
   * Returns wether splitting of fields is allowed.
   *
   * @return bool
   *   Wether category split on field level is turnded on or not.
   */
  public function allowSplit() {
    return (bool) ($this->active() && $this->configFactory->get('social_tagging.settings')->get('allow_category_split'));
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
    // Fetch main categories.
    foreach ($this->termStorage->loadTree('social_tagging', 0, 1) as $category) {
      $options[$category->tid] = $category->name;
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
    // Fetch main categories.
    foreach ($this->termStorage->loadTree('social_tagging', $category, 1) as $category) {
      $options[$category->tid] = $category->name;
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
   * @param array $terms
   *   An array of items that are selected.
   *
   * @return array
   *   An hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $terms) {

    $tree = [];

    foreach ($terms as $term) {
      if (!isset($term['target_id'])) {
        continue;
      }

      $current_term = $this->termStorage->load($term['target_id']);
      // Must be a valid Term.
      if (!$current_term instanceof TermInterface) {
        continue;
      }
      // Get current terms parents.
      $parents = $this->termStorage->loadParents($current_term->id());
      $parent = reset($parents);
      $category = $parent->getName();

      if ($this->allowSplit()) {
        $parameter = social_tagging_to_machine_name($category);
      }
      else {
        $parameter = 'tag';
      }

      $url = Url::fromRoute('view.search_content.page_no_value', [
        $parameter . '[]' => $current_term->id(),
      ]);

      $tree[$parent->id()]['title'] = $category;
      $tree[$parent->id()]['tags'][$current_term->id()] = [
        'url' => $url->toString(),
        'name' => $current_term->getName(),
      ];
    }
    // Return the tree.
    return $tree;
  }

}
