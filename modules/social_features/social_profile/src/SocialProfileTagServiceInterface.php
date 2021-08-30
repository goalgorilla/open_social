<?php

namespace Drupal\social_profile;

/**
 * Interface SocialProfileTagServiceInterface.
 *
 * @package Drupal\social_profile
 */
interface SocialProfileTagServiceInterface {

  /**
   * Returns whether the feature is turned on or not.
   *
   * @return bool
   *   Whether tagging is turned on or not.
   */
  public function isActive();

  /**
   * Returns if there are any taxonomy items available.
   *
   * @return bool
   *   If there are tags available.
   */
  public function hasContent();

  /**
   * Returns whether splitting of fields is allowed.
   *
   * @return bool
   *   Whether category split on field level is turned on or not.
   */
  public function allowSplit();

  /**
   * Returns all the top level term items, that are considered categories.
   *
   * @return array
   *   An array of top level category items.
   */
  public function getCategories();

  /**
   * Returns the children of top level term items.
   *
   * @param int $category
   *   The category you want to fetch the child items from.
   *
   * @return array
   *   An array of child items.
   */
  public function getChildrens($category);

  /**
   * Returns whether using a parent of categories is allowed.
   *
   * @return bool
   *   Whether using categories parent is turned on or not..
   */
  public function useCategoryParent();

  /**
   * Returns converted tag name to machine readable.
   *
   * @param string $label
   *   Label of term.
   *
   * @return string
   *   Tag machine name.
   */
  public function tagLabelToMachineName($label);

  /**
   * Returns a multilevel tree.
   *
   * @param array $term_ids
   *   An array of items that are selected.
   *
   * @return array
   *   An hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $term_ids);

  /**
   * Returns list of term names as option list.
   *
   * @param array $term_ids
   *   List of taxonomy term IDs.
   *
   * @return array
   *   Options.
   */
  public function getTermOptionNames(array $term_ids);

}
