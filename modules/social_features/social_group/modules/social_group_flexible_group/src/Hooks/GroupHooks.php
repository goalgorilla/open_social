<?php

namespace Drupal\social_group_flexible_group\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_group_flexible_group\Plugin\GraphQL\DataProducer\UserFlexibleGroupMemberships;

/**
 * Provides hook related to flexible group.
 */
final class GroupHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('group_content_delete')]
  #[Hook('group_content_insert')]
  public function eventCreateDelete(EntityInterface $entity): void {
    // Invalidation cache only on insert/delete, owner change is not happened
    // for groups memberships.
    if (!$entity instanceof GroupRelationshipInterface ||
      $entity->getPluginId() !== 'group_membership' ||
      $entity->getGroupTypeId() !== 'flexible_group') {
      return;
    }
    // Invalidate cache for group_membership create/delete.
    $this->cacheInvalidator->invalidateTags([UserFlexibleGroupMemberships::CID_BASE . $entity->getOwnerId()]);
  }

}
