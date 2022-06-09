<?php

namespace Drupal\social_group\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group as GroupBase;
use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\social_group\SocialGroupInterface;

/**
 * Provides a Group entity that has links that work with different languages.
 */
class Group extends GroupBase implements SocialGroupInterface {

  use EntityUrlLanguageTrait;

  /**
   * {@inheritdoc}
   */
  public function hasMember(AccountInterface $account): bool {
    return $this->getMember($account) !== FALSE;
  }

}
