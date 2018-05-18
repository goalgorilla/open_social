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
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    return $this->buildUserQuery($match);
  }

}
