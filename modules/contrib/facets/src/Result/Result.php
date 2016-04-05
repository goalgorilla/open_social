<?php

namespace Drupal\facets\Result;

use Drupal\Core\Url;

/**
 * The default implementation of the result interfaces.
 */
class Result implements ResultInterface {

  /**
   * The facet value.
   */
  protected $displayValue;

  /**
   * The raw facet value.
   */
  protected $rawValue;

  /**
   * The facet count.
   *
   * @var int
   */
  protected $count;

  /**
   * The Url object.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * Is this a selected value or not.
   *
   * @var bool
   */
  protected $active = FALSE;


  protected $children = [];

  /**
   * Constructs a new result value object.
   *
   * @param mixed $raw_value
   *   The raw value.
   * @param mixed $display_value
   *   The formatted value.
   * @param int $count
   *   The amount of items.
   */
  public function __construct($raw_value, $display_value, $count) {
    $this->rawValue = $raw_value;
    $this->displayValue = $display_value;
    $this->count = $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayValue() {
    return $this->displayValue;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValue() {
    return $this->rawValue;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl(Url $url) {
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function setCount($count) {
    $this->count = $count;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveState($active) {
    $this->active = $active;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayValue($display_value) {
    $this->displayValue = $display_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChildren(ResultInterface $children) {
    $this->children[] = $children;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    return $this->children;
  }

}
