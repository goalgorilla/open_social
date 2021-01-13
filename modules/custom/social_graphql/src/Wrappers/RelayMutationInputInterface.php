<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers;

/**
 * Mutation input conforming to the Relay specification.
 */
interface RelayMutationInputInterface extends InputInterface {

  /**
   * Get the identifier for this mutation provided by the client.
   *
   * When provided, should be included in the payload for the mutation. This
   * allows a client to match up mutations with responses (for example in case
   * of optimistic updates) regardless of transport method.
   *
   * Mutation DataProducers can use this to ensure mutations are idempotent by
   * caching the result of a mutation and returning the same response if a
   * clientMutationId is repeated. This makes it possible for clients to safely
   * retry operations such as sending messages.
   *
   * @return string
   *   The unique mutation identifier.
   */
  public function getClientMutationId() : ?string;

}
