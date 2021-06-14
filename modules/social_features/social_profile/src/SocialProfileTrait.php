<?php

namespace Drupal\social_profile;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Trait SocialProfileTrait.
 *
 * @package Drupal\social_profile
 */
trait SocialProfileTrait {

  /**
   * Add Nickname.
   *
   * @return bool
   *   Whether or not the nickname needs to be added.
   */
  private function addNickname() {
    return \Drupal::moduleHandler()->moduleExists('social_profile_fields');
  }

  /**
   * Check if can use the full name for the search.
   *
   * Allow using the full name for search when the limitation is disabled or
   * user has permission to see the full name.
   *
   * @return bool
   *   TRUE if a user can use the full name for the search.
   */
  private function useFullName() {
    return !\Drupal::config('social_profile_privacy.settings')->get('limit_search_and_mention') || \Drupal::currentUser()->hasPermission('social profile privacy always show full name');
  }

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
    $query = $this->startQuery();
    $name = '%' . ltrim($query->escapeLike($name)) . '%';

    switch ($suggestion_format) {
      case SOCIAL_PROFILE_SUGGESTIONS_USERNAME:
        $query->condition('uf.name', $name, 'LIKE');
        break;

      case SOCIAL_PROFILE_SUGGESTIONS_FULL_NAME:
      case SOCIAL_PROFILE_SUGGESTIONS_ALL:
        if ($this->useFullName()) {
          $strings = explode(' ', $name);

          if (count($strings) > 1) {
            $query->where("CONCAT(TRIM(fn.field_profile_first_name_value), ' ', TRIM(ln.field_profile_last_name_value)) LIKE :full_name", [
              ':full_name' => $name,
            ]);

            $query = $this->sortQuery($query, $name, $suggestion_format);
            $results = $this->endQuery($query, $count);

            if (count($results) > 0) {
              return $results;
            }

            // Fallback to creating a new query if there is no hit on full name.
            $query = $this->startQuery();
          }
        }

        $or_query = $query->orConditionGroup();

        if ($this->useFullName()) {
          $or_query
            ->condition('fn.field_profile_first_name_value', $name, 'LIKE')
            ->condition('ln.field_profile_last_name_value', $name, 'LIKE');
        }

        // Add name only when needed.
        if ($suggestion_format === SOCIAL_PROFILE_SUGGESTIONS_ALL) {
          $or_query->condition('uf.name', $name, 'LIKE');
        }

        if ($this->addNickName() === TRUE) {
          $or_query->condition('nn.field_profile_nick_name_value', $name, 'LIKE');
        }

        $query->condition($or_query);

        break;
    }

    // Now we sort the query.
    $query = $this->sortQuery($query, $name, $suggestion_format);

    return $this->endQuery($query, $count);
  }

  /**
   * Start a Social Profile Mention Query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Returns the query object.
   */
  private function startQuery() {
    $connection = \Drupal::database();

    $query = $connection->select('users', 'u')->fields('u', ['uid']);
    $query->join('users_field_data', 'uf', 'uf.uid = u.uid');
    $query->leftJoin('profile', 'p', 'p.uid = u.uid');

    if ($this->useFullName()) {
      $query->leftJoin('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
      $query->leftJoin('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');
    }

    if ($this->addNickName() === TRUE) {
      $query->leftJoin('profile__field_profile_nick_name', 'nn', 'nn.entity_id = p.profile_id');
    }

    $query->condition('uf.status', 1);

    return $query;
  }

  /**
   * Sorts the query.
   *
   * Following the rules:
   * 1. Users whose have first name starting by the given string;
   * 2. Users whose have last name starting by the given string;
   * 3. Users whose have nickname starting by the given string;
   * 4. Users whose have username  starting by the given string;
   * 5. Users containing the string.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param string $name
   *   The sanitized string to search for.
   * @param string $suggestion_format
   *   (optional) The suggestion format.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  private function sortQuery(SelectInterface $query, $name, $suggestion_format) {
    if ($suggestion_format !== SOCIAL_PROFILE_SUGGESTIONS_USERNAME && $this->useFullName()) {
      // Delete percent symbol on the beginning of the phrase for search from
      // the start of field values.
      $name = substr($name, 1);

      $query->addExpression("
    CASE WHEN fn.field_profile_first_name_value LIKE :name THEN 0
      WHEN ln.field_profile_last_name_value LIKE :name THEN 1
      ELSE 2
    END
  ", 'mention_sort', [':name' => $name]);
      $query->orderBy('mention_sort');
      $query->orderBy('fn.field_profile_first_name_value');
      $query->orderBy('ln.field_profile_last_name_value');
    }

    if ($this->addNickName() === TRUE) {
      $query->orderBy('nn.field_profile_nick_name_value');
    }

    $query->orderBy('uf.name');

    return $query;
  }

  /**
   * End a Social Profile Mention Query.
   *
   * @return int[]
   *   An array of account IDs for accounts whose account names begin with the
   *   given string.
   */
  private function endQuery(SelectInterface $query, $count) {
    $result = $query
      ->range(0, $count)
      ->execute()
      ->fetchCol();

    return !empty($result) ? $result : [];
  }

}
