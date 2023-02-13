<?php

namespace Drupal\social_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the tagging service interface.
 */
interface SocialTaggingServiceInterface {

  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injection of the entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Injection of the languageManager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler
  );

  /**
   * Returns whether the feature is turned on or not.
   */
  public function active(): bool;

  /**
   * Returns whether the feature is turned on for groups or not.
   */
  public function groupActive(): bool;

  /**
   * Returns whether the feature is turned on for profiles or not.
   */
  public function profileActive(): bool;

  /**
   * Returns if there are any taxonomy items available.
   */
  public function hasContent(): bool;

  /**
   * Returns whether splitting of fields is allowed.
   *
   * @return bool
   *   Whether category split on field level is turned on or not.
   */
  public function allowSplit(): bool;

  /**
   * Returns the filter query condition.
   *
   * @return string
   *   Returns 'OR' or 'AND'.
   */
  public function queryCondition(): string;

  /**
   * Returns whether using a parent of categories is allowed.
   *
   * @return bool
   *   Whether using categories parent is turned on or not.
   */
  public function useCategoryParent(): bool;

  /**
   * Returns all the top level term items, that are considered categories.
   */
  public function getCategories(): array;

  /**
   * Returns the children of top level term items.
   *
   * @param int $category
   *   The category you want to fetch the child items from.
   *
   * @return array
   *   An array of child items.
   */
  public function getChildren(int $category): array;

  /**
   * Returns all the children of top level term items.
   *
   * @return array
   *   An array of child items.
   */
  public function getAllChildren(): array;

  /**
   * Returns a multilevel tree.
   *
   * @param array $term_ids
   *   An array of items that are selected.
   * @param string $entity_type
   *   The entity type these tags are for.
   *
   * @return array
   *   A hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $term_ids, string $entity_type): array;

  /**
   * Gets supported entity types.
   *
   * @param bool $short
   *   (optional) TRUE if entity types should be got only. Defaults to FALSE.
   *
   * @return array
   *   If short mode is enabled then it returns an array of entity type
   *   identifiers, otherwise an associative array of supported entity types.
   *   The keys are entity type identifiers. The values are arrays of bundles.
   */
  public function types(bool $short = FALSE): array;

}
