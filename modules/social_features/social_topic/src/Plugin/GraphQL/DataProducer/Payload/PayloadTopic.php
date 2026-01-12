<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Payload;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\node\NodeInterface;
use Drupal\social_topic\Wrappers\Payload\CreateTopicPayload;

/**
 * Returns the topic in a payload.
 *
 * @DataProducer(
 *   id = "payload_topic",
 *   name = @Translation("Payload Topic"),
 *   description = @Translation("Returns the topic from a payload."),
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
class PayloadTopic extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\social_topic\Wrappers\Payload\CreateTopicPayload $payload
   *   The payload to return the topic for.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The topic for this payload or null if there is none.
   */
  public function resolve(CreateTopicPayload $payload): ?NodeInterface {
    return $payload->getTopic();
  }

}
