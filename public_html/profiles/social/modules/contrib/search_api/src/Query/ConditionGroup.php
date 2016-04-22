<?php

namespace Drupal\search_api\Query;

/**
 * Provides a standard implementation for a Search API query condition group.
 */
class ConditionGroup implements ConditionGroupInterface {

  /**
   * Array containing sub-conditions.
   *
   * Each of these is either an array (field, value, operator), or another
   * \Drupal\search_api\Query\ConditionGroupInterface object.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * String specifying this condition group's conjunction ('AND' or 'OR').
   *
   * @var string
   */
  protected $conjunction;

  /**
   * An array of tags set on this condition group.
   *
   * @var string[]
   */
  protected $tags;

  /**
   * Constructs a ConditionGroup object.
   *
   * @param string $conjunction
   *   (optional) The conjunction to use for this condition group - either 'AND'
   *   or 'OR'.
   * @param string[] $tags
   *   (optional) An arbitrary set of tags. Can be used to identify this
   *   condition group after it's been added to the query. This is
   *   primarily used by the facet system to support OR facet queries.
   */
  public function __construct($conjunction = 'AND', array $tags = array()) {
    $this->conjunction = strtoupper(trim($conjunction)) == 'OR' ? 'OR' : 'AND';
    $this->tags = array_combine($tags, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * {@inheritdoc}
   */
  public function addConditionGroup(ConditionGroupInterface $condition_group) {
    $this->conditions[] = $condition_group;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCondition($field, $value, $operator = '=') {
    $this->conditions[] = new Condition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getConditions() {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return isset($this->tags[$tag]);
  }

  /**
   * {@inheritdoc}
   */
  public function &getTags() {
    return $this->tags;
  }

  /**
   * Implements the magic __clone() method.
   *
   * Takes care to clone nested condition groups, too.
   */
  public function __clone() {
    foreach ($this->conditions as $i => $condition) {
      $this->conditions[$i] = clone $condition;
    }
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    // Special case for a single, nested condition group:
    if (count($this->conditions) == 1) {
      return (string) reset($this->conditions);
    }
    $ret = array();
    foreach ($this->conditions as $condition) {
      $ret[] = str_replace("\n", "\n  ", (string) $condition);
    }
    return $ret ? "[\n  " . implode("\n{$this->conjunction}\n  ", $ret) . "\n]" : '';
  }

}
