<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Payload;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\Payload\RelayMutationPayloadInterface;

/**
 * Returns the client mutation id in a payload.
 *
 * @DataProducer(
 *   id = "payload_client_mutation_id",
 *   name = @Translation("Payload client mutation id"),
 *   description = @Translation("Returns the client mutation id from a payload."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Client Mutation ID")
 *   ),
 *   consumes = {
 *     "payload" = @ContextDefinition("any",
 *       label = @Translation("Payload")
 *     )
 *   }
 * )
 */
class PayloadClientMutationId extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\social_graphql\GraphQL\Payload\RelayMutationPayloadInterface $payload
   *   The payload to return the client mutation id for.
   *
   * @return null|string
   *   The client mutation identifier.
   */
  public function resolve(RelayMutationPayloadInterface $payload) {
    return $payload->getClientMutationId();
  }

}
