<?php

namespace Drupal\facets\Result;


use Drupal\Core\Url;

/**
 * The interface defining what a facet result should look like.
 */
interface ResultInterface {

  /**
   * Returns the raw value as present in the index.
   *
   * @return string
   *   The raw value of the result.
   */
  public function getRawValue();

  /**
   * Returns the display value as present in the index.
   *
   * @return string
   *   The formatted value of the result.
   */
  public function getDisplayValue();

  /**
   * Returns the count for the result.
   *
   * @return mixed
   *   The amount of items for the result.
   */
  public function getCount();

  /**
   * Returns the url.
   *
   * @return \Drupal\Core\Url
   *   The url of the search page with the facet url appended.
   */
  public function getUrl();

  /**
   * Sets the url.
   *
   * @param \Drupal\Core\Url $url
   *   The url of the search page with the facet url appended.
   */
  public function setUrl(Url $url);

  /**
   * Indicates that the value is active (selected).
   *
   * @param bool $active
   *   A boolean indicating the active state.
   */
  public function setActiveState($active);

  /**
   * Returns true if the value is active (selected).
   *
   * @return bool $active
   *   A boolean indicating the active state.
   */
  public function isActive();

  /**
   * Overrides the display value of a result.
   *
   * @param string $display_value
   *   Override display value.
   */
  public function setDisplayValue($display_value);

  /**
   * Sets children results.
   *
   * @param \Drupal\facets\Result\ResultInterface $children
   *   The children to be added.
   */
  public function setChildren(ResultInterface $children);

  /**
   * Returns children results.
   *
   * @return \Drupal\facets\Result\ResultInterface $children
   *   The children results.
   */
  public function getChildren();

}
