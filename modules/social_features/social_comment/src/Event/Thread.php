<?php

namespace Drupal\social_comment\Event;

/**
 * Thread information for comment CloudEvents.
 */
final class Thread {

  /**
   * Constructs the Thread type.
   *
   * @param string $root_id
   *   The UUID of the root comment in the thread.
   * @param string|null $parent_id
   *   The UUID of the parent comment, or null if top-level.
   * @param int $depth
   *   The depth of the comment in the thread (0 = top level).
   */
  public function __construct(
    public readonly string $root_id,
    public readonly ?string $parent_id,
    public readonly int $depth,
  ) {}

}
