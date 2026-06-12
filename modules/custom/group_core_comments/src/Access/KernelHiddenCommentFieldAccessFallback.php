<?php

declare(strict_types=1);

namespace Drupal\group_core_comments\Access;

use Drupal\comment\CommentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_comment\HiddenCommentFieldAccessInterface;

/**
 * Neutral fallback when the social_comment module is not enabled.
 *
 * Kernel tests often enable group_core_comments without social_comment.
 * Formatters in this module type-hint HiddenCommentFieldAccessInterface, so a
 * definition must exist even when social_comment services are not loaded.
 */
final class KernelHiddenCommentFieldAccessFallback implements HiddenCommentFieldAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function accessView(CommentInterface $comment, AccountInterface $account): AccessResultInterface {
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedHiddenFieldsByNid(AccountInterface $account, array $accessible_nids): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function accessHiddenField(AccountInterface $account, FieldableEntityInterface $parent, string $field_name): AccessResultInterface {
    return AccessResult::neutral();
  }

}
