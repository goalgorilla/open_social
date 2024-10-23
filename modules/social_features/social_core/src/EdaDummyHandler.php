<?php

namespace Drupal\social_core;

/**
 * A dummy handler that equals a no-op.
 *
 * This ensures people who do not use the EDA to publish events to an external
 * event bus don't have any performance penalty from loading or formatting data.
 */
class EdaDummyHandler {

  /**
   * {@inheritDoc}
   */
  public function __call(string $name, array $arguments): void {}

}
