<?php

declare(strict_types = 1);

namespace Drupal\social_graphql\GraphQL\Payload;

use Drupal\social_graphql\GraphQL\ViolationInterface;

/**
 * Response interface used for GraphQL responses.
 */
interface PayloadInterface {

  /**
   * Adds the violation.
   *
   * @param \Drupal\social_graphql\GraphQL\ViolationInterface $violation
   *   A violation.
   *
   * @return $this
   *   This payload.
   */
  public function addViolation(ViolationInterface $violation): self;

  /**
   * Adds multiple violations.
   *
   * @param array $violations
   *   An array of violations.
   *
   * @return $this
   *   This payload.
   */
  public function addViolations(array $violations): self;

  /**
   * Gets the violations.
   *
   * @return \Drupal\social_graphql\GraphQL\ViolationInterface[]
   *   The Violations.
   */
  public function getViolations(): array;

}
