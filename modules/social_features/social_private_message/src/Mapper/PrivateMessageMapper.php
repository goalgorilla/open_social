<?php

namespace Drupal\social_private_message\Mapper;

use Drupal\private_message\Mapper\PrivateMessageMapper as PrivateMessageMapperBase;
use Drupal\social_profile\SocialProfileTrait;

/**
 * Class PrivateMessageMapper.
 *
 * @package Drupal\social_private_message\Mapper
 */
class PrivateMessageMapper extends PrivateMessageMapperBase {

  use SocialProfileTrait;

  /**
   * {@inheritdoc}
   */
  public function getUserIdsFromString($string, $count) {
    if ($this->currentUser->hasPermission('access user profiles') && $this->currentUser->hasPermission('use private messaging system')) {
      return $this->getUserIdsFromName($string, $count);
    }

    return [];
  }

}
