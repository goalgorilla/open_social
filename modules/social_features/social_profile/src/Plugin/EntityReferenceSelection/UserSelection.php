<?php

namespace Drupal\social_profile\Plugin\EntityReferenceSelection;

use Drupal\social_profile\SocialProfileTrait;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection as UserSelectionBase;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "social:user",
 *   label = @Translation("Social user selection"),
 *   entity_types = {"user"},
 *   group = "social",
 *   weight = 1
 * )
 */
class UserSelection extends UserSelectionBase {

  use SocialProfileTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['include_anonymous'] = FALSE;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->connection->select('profile', 'p')
      ->fields('p', ['uid']);

    $addNickName = $this->moduleHandler->moduleExists('social_profile_fields');

    $query->join('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
    $query->join('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');

    if ($addNickName === TRUE) {
      $query->join('profile__field_profile_nick_name', 'nn', 'nn.entity_id = p.profile_id');
    }

    $name = $this->connection->escapeLike($match);

    $or = $query->orConditionGroup();
    $or->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE');
    $or->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');

    if ($addNickName === TRUE) {
      $or->condition('nn.field_profile_nick_name_value', '%' . $name . '%', 'LIKE');
    }

    $ids = $query->condition($or)->execute()->fetchCol();

    if (empty($ids)) {
      return parent::buildEntityQuery($match, $match_operator);
    }

    $query = parent::buildEntityQuery(NULL, $match_operator);

    $or = $query->orConditionGroup();
    $or->condition('name', $match, $match_operator);
    $or->condition('uid', $ids, 'IN');

    $query->condition($or);
    $query->condition('uid', $this->currentUser->id(), '!=');

    return $query;
  }

}
