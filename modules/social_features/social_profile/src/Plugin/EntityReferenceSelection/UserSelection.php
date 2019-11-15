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
 *   weight = 1,
 *   base_plugin_label = @Translation("Social user selection")
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

    // Give the query a tag to identify it for altering.
    $query->addTag('social_entityreference');

    $query->leftJoin('profile__field_profile_first_name', 'fn', 'fn.entity_id = p.profile_id');
    $query->leftJoin('profile__field_profile_last_name', 'ln', 'ln.entity_id = p.profile_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = p.uid');

    if ($addNickName === TRUE) {
      $query->leftJoin('profile__field_profile_nick_name', 'nn', 'nn.entity_id = p.profile_id');
    }

    $name = $this->connection->escapeLike($match);

    $or = $query->orConditionGroup();
    $or->condition('ufd.name', '%' . $name . '%', 'LIKE');
    $or->condition('fn.field_profile_first_name_value', '%' . $name . '%', 'LIKE');
    $or->condition('ln.field_profile_last_name_value', '%' . $name . '%', 'LIKE');

    if ($addNickName === TRUE) {
      $or->condition('nn.field_profile_nick_name_value', '%' . $name . '%', 'LIKE');
    }

    // Only allow searching when user has permission to view.
    if ($this->currentUser->hasPermission('view profile email')) {
      $or->condition('ufd.mail', '%' . $name . '%', 'LIKE');
    }

    $ids = $query->condition($or)->execute()->fetchCol();

    if (empty($ids)) {
      return parent::buildEntityQuery($match, $match_operator);
    }

    $query = parent::buildEntityQuery(NULL, $match_operator);
    $query->condition('uid', $ids, 'IN');

    return $query;
  }

}
