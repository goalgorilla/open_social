<?php

declare(strict_types=1);

namespace Drupal\social_comment\Hooks;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\social_comment\Cache\HiddenCommentFieldMapCache;
use Drupal\social_comment\Entity\Access\CommentQueryAccessHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Invalidates hidden comment field caches when comment field status changes.
 *
 * Detection runs in presave (before storage) so the original status can be
 * compared. Cache resets run after save so the hidden field map rebuilds from
 * persisted field values.
 *
 * @internal
 */
final class HiddenCommentFieldCacheInvalidationHooks implements ContainerInjectionInterface {

  /**
   * Node keys pending hidden comment field cache reset after save.
   *
   * @var array<int|string, true>
   */
  private static array $pendingCacheResets = [];

  public function __construct(
    protected HiddenCommentFieldMapCache $hiddenCommentFieldMapCache,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_comment.hidden_comment_field_map_cache'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * Marks nodes whose hidden comment field status will change on save.
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $original = NULL;
    if (!$entity->isNew()) {
      $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
    }

    if ($this->requiresHiddenCommentFieldCacheReset($entity, $original)) {
      self::$pendingCacheResets[$this->pendingCacheResetKey($entity)] = TRUE;
    }
  }

  /**
   * Resets caches when a new node is saved with a hidden comment field.
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $this->resetPendingHiddenCommentFieldCaches($entity);
  }

  /**
   * Resets caches when a node's comment field status changed on save.
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $this->resetPendingHiddenCommentFieldCaches($entity);
  }

  /**
   * Resets caches when a pending reset was queued during presave.
   */
  private function resetPendingHiddenCommentFieldCaches(NodeInterface $node): void {
    $key = $this->pendingCacheResetKey($node);
    if (!isset(self::$pendingCacheResets[$key])) {
      return;
    }

    unset(self::$pendingCacheResets[$key]);
    $this->resetHiddenCommentFieldCaches($node);
  }

  /**
   * Whether hidden comment field status changed for this node save.
   */
  private function requiresHiddenCommentFieldCacheReset(NodeInterface $node, ?NodeInterface $original): bool {
    foreach ($node->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() !== 'comment' || !$node->hasField($field_name)) {
        continue;
      }

      $comment_field_status = $this->getCommentFieldItemStatus($node->get($field_name)->first());
      if ($original === NULL) {
        return $comment_field_status === CommentItemInterface::HIDDEN;
      }

      if (!$original->hasField($field_name)) {
        continue;
      }

      $original_comment_field_status = $this->getCommentFieldItemStatus($original->get($field_name)->first());
      $was_hidden = $original_comment_field_status === CommentItemInterface::HIDDEN;
      $is_hidden = $comment_field_status === CommentItemInterface::HIDDEN;
      if ($was_hidden !== $is_hidden) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the pending cache reset key for a node save operation.
   */
  private function pendingCacheResetKey(NodeInterface $node): int|string {
    if ($node->isNew()) {
      return 'new:' . spl_object_id($node);
    }

    return (int) $node->id();
  }

  /**
   * Returns the comment field status from a field item, if present.
   */
  private function getCommentFieldItemStatus(?FieldItemInterface $item): ?int {
    if (!$item instanceof CommentItemInterface) {
      return NULL;
    }
    $value = $item->getValue();
    return isset($value['status']) ? (int) $value['status'] : NULL;
  }

  /**
   * Resets hidden comment field map cache and related access policy tags.
   */
  private function resetHiddenCommentFieldCaches(NodeInterface $node): void {
    $this->hiddenCommentFieldMapCache->reset();
    CommentQueryAccessHandler::resetNodeCommentAccessCache();
    $this->cacheTagsInvalidator->invalidateTags(array_merge(
      $node->getCacheTagsToInvalidate(),
      ['access_policies'],
    ));
  }

}
