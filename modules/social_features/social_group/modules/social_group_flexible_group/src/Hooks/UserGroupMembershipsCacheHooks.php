<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\hux\Attribute\Hook;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cache invalidation for User.groupMemberships GraphQL results.
 *
 * @package Drupal\social_group_flexible_group\Hooks
 */
final class UserGroupMembershipsCacheHooks implements ContainerInjectionInterface {

  /**
   * UserGroupMembershipsCacheHooks constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected Connection $connection,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('cache_tags.invalidator'),
      $container->get('database'),
    );
  }

  /**
   * Invalidates membership list cache when a flexible group membership changes.
   */
  #[
    Hook('group_content_insert'),
    Hook('group_content_delete'),
  ]
  public function groupMembershipChange(GroupRelationshipInterface $group_content): void {
    $this->invalidateMemberMembershipListTag($group_content);
  }

  /**
   * Invalidates membership list cache when group visibility or status changes.
   */
  #[Hook('group_update')]
  public function groupUpdate(GroupInterface $group): void {
    if ($group->bundle() !== 'flexible_group') {
      return;
    }

    if (!$this->groupViewAccessInputsChanged($group)) {
      return;
    }

    $this->invalidateMembershipListTagsForGroupMembers($group);
  }

  /**
   * Invalidates membership list cache when a flexible group is unpublished.
   */
  #[Hook('social_core_group_unpublished')]
  public function groupUnpublished(EntityInterface $entity): void {
    if (!$entity instanceof GroupInterface || $entity->bundle() !== 'flexible_group') {
      return;
    }

    $this->invalidateMembershipListTagsForGroupMembers($entity);
  }

  /**
   * Invalidates membership list cache when a flexible group is published.
   */
  #[Hook('social_core_group_published')]
  public function groupPublished(EntityInterface $entity): void {
    if (!$entity instanceof GroupInterface || $entity->bundle() !== 'flexible_group') {
      return;
    }

    $this->invalidateMembershipListTagsForGroupMembers($entity);
  }

  /**
   * Invalidates the per-user flexible group membership list tag for one member.
   */
  private function invalidateMemberMembershipListTag(GroupRelationshipInterface $group_content): void {
    if (!$group_content instanceof GroupMembershipInterface) {
      return;
    }
    if ($group_content->getGroup()->bundle() !== 'flexible_group') {
      return;
    }

    $user_id = $group_content->getEntityId();
    if ($user_id === '') {
      return;
    }

    $this->cacheTagsInvalidator->invalidateTags([
      "group_content_list:plugin:group_membership:entity:$user_id",
    ]);
  }

  /**
   * Invalidates membership list tags for every member of the group.
   */
  private function invalidateMembershipListTagsForGroupMembers(GroupInterface $group): void {
    $result = $this->connection->select('group_relationship_field_data', 'grfd')
      ->fields('grfd', ['entity_id'])
      ->condition('grfd.gid', $group->id())
      ->condition('grfd.plugin_id', 'group_membership')
      ->execute();
    if ($result === NULL) {
      return;
    }

    $user_ids = array_filter(
      array_unique($result->fetchCol()),
      static fn (string|int|null $user_id): bool => $user_id !== '' && $user_id !== NULL,
    );
    if ($user_ids === []) {
      return;
    }

    $tags = [];
    foreach ($user_ids as $user_id) {
      $tags[] = "group_content_list:plugin:group_membership:entity:$user_id";
    }
    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

  /**
   * Whether group fields affecting viewer-visible memberships changed.
   */
  private function groupViewAccessInputsChanged(GroupInterface $group): bool {
    if (!isset($group->original) || !$group->original instanceof GroupInterface) {
      return FALSE;
    }

    $original = $group->original;

    if ($group->isPublished() !== $original->isPublished()) {
      return TRUE;
    }

    if (!$group->hasField('field_flexible_group_visibility')) {
      return FALSE;
    }

    return $group->get('field_flexible_group_visibility')->getValue()
      !== $original->get('field_flexible_group_visibility')->getValue();
  }

}
