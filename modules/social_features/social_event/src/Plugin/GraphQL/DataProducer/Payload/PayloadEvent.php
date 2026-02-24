<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Payload;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\node\NodeInterface;
use Drupal\social_event\Wrappers\Payload\CreateEventPayload;

/**
 * Returns the event in a payload.
 *
 * @DataProducer(
 *   id = "payload_event",
 *   name = @Translation("Payload Event"),
 *   description = @Translation("Returns the event from a payload."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("NodeInterface")
 *   ),
 *   consumes = {
 *     "payload" = @ContextDefinition("any",
 *       label = @Translation("Payload")
 *     )
 *   }
 * )
 */
class PayloadEvent extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\social_event\Wrappers\Payload\CreateEventPayload $payload
   *   The payload to return the event for.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The event for this payload or null if there is none.
   */
  public function resolve(CreateEventPayload $payload): ?NodeInterface {
    return $payload->getEvent();
  }

}
