<?php

namespace Drupal\social_profile;

/**
 * Trait SocialProfileTrait.
 *
 * @package Drupal\social_profile
 */
trait SocialProfileTrait {

  /**
   * Get a list of account IDs whose account names begin with the given string.
   *
   * @param string $name
   *   The string to search for.
   * @param int $count
   *   The number of results to return.
   * @param string $suggestion_format
   *   (optional) The suggestion format.
   *
   * @return int[]
   *   An array of account IDs for accounts whose account names begin with the
   *   given string.
   */
  public function getUserIdsFromName($name, $count, $suggestion_format = SOCIAL_PROFILE_SUGGESTIONS_ALL) {
    $connection = \Drupal::database();

    $query = $connection->select('users', 'u')->fields('u', ['uid']);
    $query->join('users_field_data', 'uf', 'uf.uid = u.uid');
    $query->condition('uf.status', 1);

    $name = $connection->escapeLike($name);

    switch ($suggestion_format) {
      case SOCIAL_PROFILE_SUGGESTIONS_USERNAME:
        $query->condition('uf.name', '%' . $name . '%', 'LIKE');
        break;

      case SOCIAL_PROFILE_SUGGESTIONS_FULL_NAME:
        $query->join('profile', 'p', 'p.uid = u.uid');
        $query->join('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
        $query->join('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');

        $or = $query->orConditionGroup();
        $or
          ->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE')
          ->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');
        $query->condition($or);
        break;

      case SOCIAL_PROFILE_SUGGESTIONS_ALL:
        $query->leftJoin('profile', 'p', 'p.uid = u.uid');
        $query->leftJoin('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
        $query->leftJoin('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');

        $or = $query->orConditionGroup();
        $or
          ->condition('uf.name', '%' . $name . '%', 'LIKE')
          ->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE')
          ->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');
        $query->condition($or);
        break;
    }

    $result = $query
      ->range(0, $count)
      ->execute()
      ->fetchCol();

    return !empty($result) ? $result : [];
  }

}
