<?php

namespace Drupal\social_group\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\user\EntityOwnerInterface;

/**
 * Lets an owner view their own unpublished grouped content.
 *
 * Open Social is user-centric: ownership trumps group visibility. Group is not,
 * and no flexible_group role holds "view own unpublished <plugin> entity", so
 * its default handler ends on an explicit AccessResult::forbidden() that vetoes
 * the "allowed" core and entity_access_by_field return for the owner.
 *
 * This downgrades that one case to "neutral" so the node access handlers
 * decide. Neutral, not "allowed": it removes Group's veto, it grants nothing.
 * Every other case is delegated untouched.
 *
 * It decorates the generic handler, not the group_node one, so it also reaches
 * group_media and the media directory's folder terms. Accepted: the rule is the
 * same one, and "neutral" leaves those entities' own handlers the final say.
 *
 * @see https://getopensocial.atlassian.net/browse/PROD-36392
 */
class OwnUnpublishedGroupContentAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new OwnUnpublishedGroupContentAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }

    // AccessResult, not AccessResultInterface: the cacheability of the result
    // below is merged from this one, and addCacheableDependency() silently
    // drops to max-age 0 for anything that is not a cacheable dependency.
    $parent_result = $this->parent->entityAccess($entity, $operation, $account, TRUE);
    assert($parent_result instanceof AccessResult);

    // Anonymous is excluded on purpose: uid 0 owning content is not an
    // ownership claim, and lifting the veto there would expose unpublished
    // content to every visitor. Ids are cast to string because entity keys and
    // session ids are not consistently typed.
    $is_own_unpublished = $operation === 'view'
      && $entity instanceof EntityPublishedInterface
      && !$entity->isPublished()
      && $entity instanceof EntityOwnerInterface
      && $account->isAuthenticated()
      && (string) $entity->getOwnerId() === (string) $account->id();

    if (!$parent_result->isForbidden() || !$is_own_unpublished) {
      return $return_as_object ? $parent_result : $parent_result->isAllowed();
    }

    // Keep the parent's cacheability, and restate the two axes this decision
    // varies on itself: who is asking (ownership) and the entity (its owner and
    // publication status can both change).
    $result = AccessResult::neutral()
      ->addCacheableDependency($parent_result)
      ->addCacheableDependency($entity)
      ->cachePerUser();

    return $return_as_object ? $result : $result->isAllowed();
  }

}
