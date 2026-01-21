<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Payload;

use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Payload\Payload;

/**
 * The update topic payload.
 */
class UpdateTopicPayload extends Payload {

  /**
   * The updated topic node.
   */
  protected ?NodeInterface $topic = NULL;

  /**
   * Set the updated topic.
   *
   * @param \Drupal\node\NodeInterface $topic
   *   The updated topic node.
   *
   * @return $this
   */
  public function setTopic(NodeInterface $topic): self {
    $this->topic = $topic;
    return $this;
  }

  /**
   * Get the updated topic.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The updated topic node or NULL.
   */
  public function getTopic(): ?NodeInterface {
    return $this->topic;
  }

}
