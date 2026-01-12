<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Payload;

use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Payload\Payload;

/**
 * The creation topic payload.
 */
class CreateTopicPayload extends Payload {

  /**
   * The created topic node.
   */
  protected ?NodeInterface $topic = NULL;

  /**
   * Set the created topic.
   *
   * @param \Drupal\node\NodeInterface $topic
   *   The created topic node.
   *
   * @return $this
   */
  public function setTopic(NodeInterface $topic): self {
    $this->topic = $topic;
    return $this;
  }

  /**
   * Get the created topic.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The created topic node or NULL.
   */
  public function getTopic(): ?NodeInterface {
    return $this->topic;
  }

}
