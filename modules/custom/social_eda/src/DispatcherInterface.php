<?php

namespace Drupal\social_eda;

use CloudEvents\CloudEventInterface;

/**
 * Interface for dispatching CloudEvents to the message broker.
 */
interface DispatcherInterface {

  /**
   * Dispatch events to Kafka.
   *
   * @param string $topic
   *   The topic name.
   * @param \CloudEvents\CloudEventInterface $event
   *   The event to dispatch.
   */
  public function dispatch(string $topic, CloudEventInterface $event): void;

}
