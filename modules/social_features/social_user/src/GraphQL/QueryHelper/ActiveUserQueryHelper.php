<?php

namespace Drupal\social_user\GraphQL\QueryHelper;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Loads users.
 */
class ActiveUserQueryHelper extends UserQueryHelper {

  /**
   * {@inheritdoc}
   */
  public function getQuery() : QueryInterface {
    return parent::getQuery()
      // Filter out blocked users.
      ->condition('status', 1);
  }

}
