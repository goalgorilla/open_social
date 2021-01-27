<?php

namespace Drupal\social_graphql\Wrappers;

/**
 * Default implementation for edges.
 */
class Edge implements EdgeInterface {

  /**
   * The node for this edge.
   *
   * @var mixed
   */
  protected $node;

  /**
   * The cursor for this edge.
   *
   * @var string
   */
  protected string $cursor;

  /**
   * EntityEdge constructor.
   *
   * @param mixed $node
   *   The node for this edge.
   * @param string $cursor
   *   The cursor for this edge.
   */
  public function __construct($node, string $cursor) {
    $this->node = $node;
    $this->cursor = $cursor;
  }

  /**
   * {@inheritdoc}
   */
  public function getCursor() : string {
    return $this->cursor;
  }

  /**
   * {@inheritdoc}
   */
  public function getNode() {
    return $this->node;
  }

}
