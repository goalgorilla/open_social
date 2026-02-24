<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Payload;

use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Payload\Payload;

/**
 * The creation event payload.
 */
class CreateEventPayload extends Payload {

  /**
   * The created event node.
   */
  protected ?NodeInterface $event = NULL;

  /**
   * Set the created event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The created event node.
   *
   * @return $this
   */
  public function setEvent(NodeInterface $event): self {
    $this->event = $event;
    return $this;
  }

  /**
   * Get the created event.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The created event node or NULL.
   */
  public function getEvent(): ?NodeInterface {
    return $this->event;
  }

}
