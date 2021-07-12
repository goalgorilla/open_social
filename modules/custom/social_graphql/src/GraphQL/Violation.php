<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

/**
 * A violation in some GraphQL input.
 *
 * @todo https://www.drupal.org/project/social/issues/3191621
 */
class Violation implements ViolationInterface {

  /**
   * The machine readable ID for this violation.
   */
  protected string $id;

  /**
   * Create a new violation.
   *
   * @param string $id
   *   A constant understandable to machines as documented in the schema.
   */
  public function __construct(string $id) {
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return $this->id;
  }

  /**
   * Magic method called during serialization to string.
   *
   * @return string
   *   String representation of the object
   */
  public function __toString() : string {
    $encoded = json_encode($this);
    return $encoded === FALSE ? "" : $encoded;
  }

}
