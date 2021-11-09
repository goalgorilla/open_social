<?php

namespace Drupal\social_user\Entity;

use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\social_user\VerifyableUserInterface;
use Drupal\user\Entity\User as UserBase;

/**
 * Provides a User entity with Open Social required changes.
 */
class User extends UserBase implements VerifyableUserInterface {

  // Ensures the entity's links work with different languages.
  use EntityUrlLanguageTrait;

  /**
   * {@inheritdoc}
   */
  public function isVerified() : bool {
    return $this->hasRole('verified');
  }

}
