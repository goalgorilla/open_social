<?php

namespace Drupal\social_comment;

use Drupal\Core\Access\AccessResult;
use Drupal\comment\CommentFieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Override default item list class for comment fields.
 */
class SocialCommentFieldItemList extends CommentFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation === 'edit') {
      // Only users with administer own comment settings permission can edit
      // the comment status field.
      $result = AccessResult::allowedIfHasPermission($account ?: \Drupal::currentUser(), 'administer own comments');
      return $return_as_object ? $result : $result->isAllowed();
    }
    return parent::access($operation, $account, $return_as_object);
  }

}
