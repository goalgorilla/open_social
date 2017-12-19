<?php

namespace Drupal\social_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides a custom tagging service.
 */
class SocialTaggingService {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   Injection of the entitymanager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityManagerInterface $entityManager, ConfigFactoryInterface $configFactory) {
    $this->entityManager = $entityManager;
    $this->termStorage = $entityManager->getStorage('taxonomy_term');
    $this->configFactory = $configFactory;
  }

  /**
   * Returns all the top level term items, that are considered categories.
   *
   * @return bool
   *   Wether tagging is turnded on or not.
   */
  public function active() {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('enable_content_tagging');
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

}
