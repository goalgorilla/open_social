<?php

declare(strict_types = 1);

namespace Drupal\social_graphql\GraphQL\Payload;

use Drupal\social_graphql\GraphQL\ViolationInterface;

/**
 * Base class for responses containing the violations.
 */
class Payload implements PayloadInterface {

  /**
   * List of violations.
   *
   * @var \Drupal\social_graphql\GraphQL\ViolationInterface[]
   */
  protected array $violations = [];

  /**
   * {@inheritdoc}
   */
  public function addViolation(ViolationInterface $violation): self {
    $this->violations[] = $violation;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addViolations(array $violations): self {
    foreach ($violations as $violation) {
      $this->addViolation($violation);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations(): array {
    return $this->violations;
  }

}
