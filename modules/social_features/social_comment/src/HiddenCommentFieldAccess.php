<?php

declare(strict_types=1);

namespace Drupal\social_comment;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccessPolicyProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_comment\Access\HiddenCommentFieldAccessPolicy;
use Drupal\social_comment\Cache\HiddenCommentFieldMapCache;

/**
 * Revokes comment view access when the parent comment field is Hidden.
 */
final class HiddenCommentFieldAccess implements HiddenCommentFieldAccessInterface {

  public function __construct(
    protected HiddenCommentFieldMapCache $hiddenCommentFieldMapCache,
    protected AccessPolicyProcessorInterface $accessPolicyProcessor,
  ) {}

  /**
   * Applies hidden-field view access for a single comment.
   */
  public function accessView(CommentInterface $comment, AccountInterface $account): AccessResultInterface {
    $parent = $comment->getCommentedEntity();
    if (!$parent instanceof FieldableEntityInterface) {
      return AccessResult::neutral();
    }

    return $this->accessHiddenField($account, $parent, $comment->getFieldName());
  }

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
  public function getExcludedHiddenFieldsByNid(AccountInterface $account, array $accessible_nids): array {
    $hidden_map = $this->hiddenCommentFieldMapCache->getMap();
    if ($hidden_map === [] || $account->hasPermission('administer comments')) {
      return [];
    }

    $accessible_lookup = array_flip($accessible_nids);
    $calculated_permissions = $this->accessPolicyProcessor->processAccessPolicies(
      $account,
      HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD,
    );

    $excluded = [];
    foreach ($hidden_map as $nid => $field_names) {
      if (!isset($accessible_lookup[$nid])) {
        continue;
      }
      foreach ($field_names as $field_name) {
        $item = $calculated_permissions->getItem(
          HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD,
          HiddenCommentFieldAccessPolicy::identifier($nid, $field_name),
        );
        if (!$item || !$item->hasPermission(HiddenCommentFieldAccessPolicy::PERMISSION_VIEW_HIDDEN)) {
          $excluded[$nid][] = $field_name;
        }
      }
    }

    return $excluded;
  }

  /**
   * Checks view access for a hidden comment field on a parent entity.
   */
  public function accessHiddenField(AccountInterface $account, FieldableEntityInterface $parent, string $field_name): AccessResultInterface {
    if (!$this->isHiddenOnParent($parent, $field_name)) {
      return AccessResult::neutral();
    }

    if ($account->hasPermission('administer comments')) {
      return AccessResult::neutral()->cachePerPermissions();
    }

    $parent_id = $parent->id();
    if ($parent_id === NULL) {
      return AccessResult::neutral();
    }

    $calculated_permissions = $this->accessPolicyProcessor->processAccessPolicies(
      $account,
      HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD,
    );
    $item = $calculated_permissions->getItem(
      HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD,
      HiddenCommentFieldAccessPolicy::identifier($parent_id, $field_name),
    );

    if ($item && $item->hasPermission(HiddenCommentFieldAccessPolicy::PERMISSION_VIEW_HIDDEN)) {
      return AccessResult::neutral()
        ->cachePerUser()
        ->addCacheableDependency($parent)
        ->addCacheableDependency($calculated_permissions);
    }

    return AccessResult::forbidden('Access to comments is revoked because the comment field is hidden.')
      ->cachePerUser()
      ->addCacheableDependency($parent)
      ->addCacheableDependency($calculated_permissions);
  }

  /**
   * Whether the parent comment field is hidden.
   */
  protected function isHiddenOnParent(FieldableEntityInterface $parent, string $field_name): bool {
    if (!$parent->hasField($field_name)) {
      return FALSE;
    }

    $item = $parent->get($field_name)->first();
    if (!$item instanceof CommentItemInterface) {
      return FALSE;
    }
    $value = $item->getValue();
    return isset($value['status']) && (int) $value['status'] === CommentItemInterface::HIDDEN;
  }

}
