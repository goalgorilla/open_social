<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Payload;

use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Payload\Payload;

/**
 * The update event payload.
 */
class UpdateEventPayload extends Payload {

  /**
   * The updated event node.
   */
  protected ?NodeInterface $event = NULL;

  /**
   * Set the updated event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The updated event node.
   *
   * @return $this
   */
  public function setEvent(NodeInterface $event): self {
    $this->event = $event;
    return $this;
  }

  /**
   * Get the updated event.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The updated event node or NULL.
   */
  public function getEvent(): ?NodeInterface {
    return $this->event;
  }

}
