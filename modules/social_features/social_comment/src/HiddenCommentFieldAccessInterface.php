<?php

declare(strict_types=1);

namespace Drupal\social_comment;

use Drupal\comment\CommentInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for hidden comment field view access checks.
 */
interface HiddenCommentFieldAccessInterface {

  /**
   * Applies hidden-field view access for a single comment.
   */
  public function accessView(CommentInterface $comment, AccountInterface $account): AccessResultInterface;

  /**
   * Returns hidden (nid, field) pairs to exclude from comment list queries.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param int[] $accessible_nids
   *   Node IDs the account may view.
   *
   * @return array<int, string[]>
   *   Node ID to comment field names that must be excluded from lists.
   */
  public function getExcludedHiddenFieldsByNid(AccountInterface $account, array $accessible_nids): array;

  /**
   * Checks view access for a hidden comment field on a parent entity.
   */
  public function accessHiddenField(AccountInterface $account, FieldableEntityInterface $parent, string $field_name): AccessResultInterface;

}
