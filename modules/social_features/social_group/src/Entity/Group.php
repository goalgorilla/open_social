<?php

namespace Drupal\social_group\Entity;

use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\group\Entity\Group as GroupBase;

/**
 * Provides a Node entity that has links that work with different languages.
 */
class Group extends GroupBase {
  use EntityUrlLanguageTrait;

}
