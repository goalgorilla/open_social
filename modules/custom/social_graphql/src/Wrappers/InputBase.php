<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides a base class for input types.
 */
abstract class InputBase implements InputInterface {

  /**
   * Any violations that may have been discovered.
   *
   * @var \Drupal\social_graphql\GraphQL\ViolationInterface[]
   */
  protected array $violations = [];

  /**
   * {@inheritdoc}
   */
  public function hasViolations() : bool {
    return !empty($this->violations);
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations() : array {
    return $this->violations;
  }

}
