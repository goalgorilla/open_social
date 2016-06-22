<?php

namespace Drupal\search_api\Query;

use Drupal\search_api\Item\ItemInterface;

/**
 * Represents the result set of a search query.
 */
class ResultSet implements \IteratorAggregate, ResultSetInterface {

  /**
   * The executed query.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected $query;

  /**
   * The total result count.
   *
   * @var int
   */
  protected $resultCount;

  /**
   * The result items.
   *
   * @var \Drupal\search_api\Item\ItemInterface[]
   */
  protected $resultItems = array();

  /**
   * A numeric array of translated, sanitized warning messages.
   *
   * @var string[]
   */
  protected $warnings = array();

  /**
   * A numeric array of search keys that were ignored.
   *
   * @var string[]
   */
  protected $ignoredSearchKeys = array();

  /**
   * Extra data set on this search result.
   *
   * @var array
   */
  protected $extraData = array();

  /**
   * Constructs a ResultSet object.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The executed query.
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultCount() {
    return $this->resultCount;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultCount($result_count) {
    $this->resultCount = $result_count;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultItems() {
    return $this->resultItems;
  }

  /**
   * {@inheritdoc}
   */
  public function addResultItem(ItemInterface $result_item) {
    $this->resultItems[$result_item->getId()] = $result_item;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultItems(array $result_items) {
    $this->resultItems = $result_items;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWarnings() {
    return $this->warnings;
  }

  /**
   * {@inheritdoc}
   */
  public function addWarning($warning) {
    $this->warnings[] = $warning;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWarnings(array $warnings) {
    $this->warnings = $warnings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIgnoredSearchKeys() {
    return array_values($this->ignoredSearchKeys);
  }

  /**
   * {@inheritdoc}
   */
  public function addIgnoredSearchKey($ignored_search_key) {
    $this->ignoredSearchKeys[$ignored_search_key] = $ignored_search_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIgnoredSearchKeys(array $ignored_search_keys) {
    $this->ignoredSearchKeys = array_combine($ignored_search_keys, $ignored_search_keys);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraData($key) {
    return array_key_exists($key, $this->extraData);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData($key, $default = NULL) {
    return array_key_exists($key, $this->extraData) ? $this->extraData[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function &getAllExtraData() {
    return $this->extraData;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraData($key, $data = NULL) {
    if (isset($data)) {
      $this->extraData[$key] = $data;
    }
    else {
      unset($this->extraData[$key]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->resultItems);
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $out = $this->getResultCount() . ' results';
    if ($this->getResultItems()) {
      $out .= ':';
      foreach ($this->getResultItems() as $item) {
        $item = str_replace("\n", "\n  ", "$item");
        $out .= "\n- " . $item;
      }
    }
    if ($this->getWarnings()) {
      $out .= "\nWarnings:";
      foreach ($this->getWarnings() as $warning) {
        $out .= "\n- " . $warning;
      }
    }
    if ($this->getIgnoredSearchKeys()) {
      $out .= "\nIgnored keys:";
      foreach ($this->getIgnoredSearchKeys() as $ignored_key) {
        $out .= "\n- " . $ignored_key;
      }
    }
    if ($this->getAllExtraData()) {
      $data = str_replace("\n", "\n  ", print_r($this->getAllExtraData(), TRUE));
      $out .= "\nExtra data: " . $data;
    }
    return $out;
  }

}
