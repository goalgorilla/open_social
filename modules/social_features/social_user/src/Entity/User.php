<?php

namespace Drupal\social_user\Entity;

use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\user\Entity\User as UserBase;

/**
 * Provides a User entity that has links that work with different languages.
 */
class User extends UserBase {
  use EntityUrlLanguageTrait;

}
