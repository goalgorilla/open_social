<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

/**
 * Provides helper functions to deal with groups.
 */
trait GroupTrait {

  /**
   * Get the group from a group title.
   *
   * @param string $group_title
   *   The title of the group.
   *
   * @return int|null
   *   The integer ID of the group or NULL if no group could be found.
   */
  protected function getNewestGroupIdFromTitle($group_title) : ?int {
    $query = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->condition('label', $group_title);

    $group_ids = $query->execute();

    // We always return the last group with the title in case of duplicates.
    // This is almost always the intention and more often so than not finding
    // anything in case of duplicates. Tests should prefer not having duplicate
    // titles, but testing that duplicate titles are allowed (and e.g. how path
    // aliases handles that) is a legitimate use case.
    return ((int) end($group_ids)) ?: NULL;
  }

}
