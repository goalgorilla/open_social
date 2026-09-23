<?php

declare(strict_types=1);

namespace Drupal\activity_creator;

/**
 * Tracks whether content is entering its groups for the first time.
 *
 * The marker is raised either because the content is being created, or because
 * an existing unpublished node is being published. Both moments must fan out
 * notifications once per group the content is cross-posted to, while a later
 * edit must not. The flag is request scoped on purpose: it is raised around
 * the loop that creates the group relationships (or replays their creation on
 * publish), and a finally block always restores the value it replaced.
 */
final class FirstPublicationMarker {

  /**
   * TRUE while content is entering its groups for the first time.
   *
   * @var bool
   */
  private static bool $raised = FALSE;

  /**
   * Checks whether content is currently being made visible in its groups.
   *
   * @return bool
   *   TRUE when the marker is raised.
   */
  public static function isRaised(): bool {
    return self::$raised;
  }

  /**
   * Raises or lowers the marker.
   *
   * @param bool $raised
   *   TRUE to raise the marker, FALSE to lower it.
   */
  public static function setRaised(bool $raised): void {
    self::$raised = $raised;
  }

}
