<?php

declare(strict_types=1);

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Decides which group relationships an account is not allowed to detach.
 *
 * An account has standing over a group when it is allowed to create this kind
 * of content in it. What it was never offered, it cannot destroy: a group the
 * entity is in but the account has no standing over is locked, and every write
 * path has to honour that.
 *
 * This class is the single owner of that rule. It is deliberately not inlined
 * in its consumers, because there are two enforcement points and they would
 * otherwise drift apart:
 *
 * - \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList::postSave()
 *   covers every write that goes through an entity save - the node form,
 *   JSON:API, migrations and programmatic saves;
 * - \Drupal\social_group\SetGroupsForNodeService::setGroupsForNode() covers the
 *   writes that delete group_content entities directly, outside any entity
 *   save, which is what the GraphQL updateTopic and updateEvent mutations do.
 */
class GroupRelationshipStanding {

  /**
   * Constructs a GroupRelationshipStanding object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The account the current write is performed for.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GroupRelationTypeManagerInterface $groupRelationTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Gets the group ids the acting account may not detach the entity from.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose group relationships are being reconciled.
   * @param \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships
   *   The entity's existing group relationships, keyed by group id.
   *
   * @return array<int|string, int|string>
   *   The group ids to protect, keyed by group id.
   */
  public function lockedGroupIds(EntityInterface $entity, array $group_relationships): array {
    // Unwrap the account proxy. The proxy is not the acting account, it merely
    // points at it, and code that identifies the caller by its class cannot see
    // through it: social_oauth's GroupPermissionCheckerDecorator only applies
    // an OAuth token scope permissions when it is handed a TokenAuthUser, so
    // judging the proxy would deny standing to every correctly scoped API
    // client.
    $account = $this->currentUser instanceof AccountProxyInterface
      ? $this->currentUser->getAccount()
      : $this->currentUser;

    // A write with no user behind it - cron, a queue worker, a Drush command, a
    // migration - runs as the anonymous session. Reading that as "standing on
    // nothing" would make every group relationship on the site immutable to
    // automation, so such writes keep the unrestricted behaviour. The
    // protection targets an identified account acting on content it does not
    // fully own.
    if ($account->isAnonymous()) {
      return [];
    }

    $entity_type_id = $entity->getEntityTypeId();
    $definition = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    $admin_permission = $entity_type_id === 'node'
      ? 'administer nodes'
      : $definition?->getAdminPermission();

    // Site-wide group and content administrators keep full control. That is
    // also what leaves uid 1 - and therefore admin tooling running as it -
    // unaffected, since it is granted every permission.
    if (
      $account->hasPermission('manage all groups') ||
      (is_string($admin_permission) && $account->hasPermission($admin_permission))
    ) {
      return [];
    }

    $locked = [];

    foreach ($group_relationships as $gid => $group_relationship) {
      $plugin_id = $group_relationship->getPluginId();

      // A relationship left behind by an uninstalled relation plugin has no
      // permission provider to ask. Writing the entity must not become fatal
      // over it, so such a leftover is simply not protected.
      if (!$this->groupRelationTypeManager->hasDefinition($plugin_id)) {
        continue;
      }

      $permission = $this->groupRelationTypeManager
        ->getPermissionProvider($plugin_id)
        ->getPermission('create', 'entity');

      // Relations that grant no entity create permission - group_membership is
      // the one in this distribution - are left alone: they have no equivalent
      // notion of standing, and inventing one would freeze them.
      if (!is_string($permission)) {
        continue;
      }

      // The group module's PHPDoc promises a non-null group, but a relationship
      // whose group was deleted out from under it survives in storage. Treating
      // that orphan as locked would freeze a node nobody can repair, so it is
      // skipped rather than protected.
      $group = $group_relationship->getGroup();
      if ($group === NULL) {
        continue;
      }

      if (!$group->hasPermission($permission, $account)) {
        $locked[$gid] = $gid;
      }
    }

    return $locked;
  }

}
