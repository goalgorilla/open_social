<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers;

use Drupal\Component\Uuid\Uuid;
use Drupal\social_graphql\GraphQL\Violation;

/**
 * Provides a base class for input types.
 *
 * Although not required for GraphQL inputs, this InputBase supports the
 * clientMutationID input value. It's encouraged that DataProducers make use of
 * this value.
 */
abstract class InputBase implements RelayMutationInputInterface {

  /**
   * Any violations that may have been discovered.
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
  public function hasViolations() : bool {
    return !empty($this->violations);
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations() : array {
    return $this->violations;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $input) : void {
    if (!empty($input['clientMutationId'])) {
      if (Uuid::isValid($input['clientMutationId'])) {
        $this->clientMutationId = $input['clientMutationId'];
      }
      else {
        $this->violations[] = new Violation("INVALID_CLIENT_MUTATION_ID");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClientMutationId() : ?string {
    return $this->clientMutationId;
  }

}
