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
  protected function getGroupIdFromTitle($group_title) : ?int {
    $query = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->condition('label', $group_title);

    $group_ids = $query->execute();
    $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple($group_ids);

    if (count($groups) !== 1) {
      return NULL;
    }

    $group_id = (int) reset($groups)->id();
    if ($group_id !== 0) {
      return $group_id;
    }

    return NULL;
  }
}
