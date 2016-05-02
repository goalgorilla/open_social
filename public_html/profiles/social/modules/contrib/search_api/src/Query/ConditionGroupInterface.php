<?php

namespace Drupal\search_api\Query;

/**
 * Represents a condition group on a search query.
 *
 * Condition groups can contain both basic (field operator value) conditions
 * and nested condition groups.
 */
interface ConditionGroupInterface extends ConditionSetInterface {

  /**
   * Retrieves the conjunction used by this condition group.
   *
   * @return string
   *   The conjunction used by this condition group – either 'AND' or 'OR'.
   */
  public function getConjunction();

  /**
   * Retrieves all conditions and nested condition groups of this object.
   *
   * @return \Drupal\search_api\Query\ConditionInterface[]|\Drupal\search_api\Query\ConditionGroupInterface[]
   *   An array containing this object's conditions. Each of these is either a
   *   simple condition, represented as an object of type
   *   \Drupal\search_api\Query\ConditionInterface, or a nested condition group,
   *   represented by a \Drupal\search_api\Query\ConditionGroupInterface object.
   *   Returned by reference so it's possible to, e.g., remove conditions.
   */
  public function &getConditions();

  /**
   * Checks whether a certain tag was set on this condition group.
   *
   * @param string $tag
   *   A tag to check for.
   *
   * @return bool
   *   TRUE if the tag was set for this condition group, FALSE otherwise.
   */
  public function hasTag($tag);

  /**
   * Retrieves the tags set on this condition group.
   *
   * @return string[]
   *   The tags associated with this condition group, as both the array keys and
   *   values. Returned by reference so it's possible to, e.g., remove existing
   *   tags.
   */
  public function &getTags();

}
