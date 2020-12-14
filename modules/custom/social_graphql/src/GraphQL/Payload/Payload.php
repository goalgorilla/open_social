<?php

declare(strict_types = 1);

namespace Drupal\social_graphql\GraphQL\Payload;

use Drupal\social_graphql\GraphQL\ViolationInterface;

/**
 * Base class for responses containing the violations.
 */
class Payload implements RelayMutationPayloadInterface {

  /**
   * List of violations.
   *
   * @var \Drupal\social_graphql\GraphQL\ViolationInterface[]
   */
  protected array $violations = [];

  /**
   * A unique identifier for the client performing the mutation.
   */
  protected ?string $clientMutationId = NULL;

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

  /**
   * {@inheritdoc}
   */
  public function setClientMutationId(?string $client_mutation_id): self {
    $this->clientMutationId = $client_mutation_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientMutationId(): ?string {
    return $this->clientMutationId;
  }

}
