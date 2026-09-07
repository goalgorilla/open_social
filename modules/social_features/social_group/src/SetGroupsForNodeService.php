<?php

namespace Drupal\social_group;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SetGroupsForNodeService.
 *
 * @package Drupal\social_group
 */
class SetGroupsForNodeService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The single owner of the group relationship standing rule.
   *
   * @var \Drupal\social_group\GroupRelationshipStanding
   */
  protected GroupRelationshipStanding $groupRelationshipStanding;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\social_group\GroupRelationshipStanding $group_relationship_standing
   *   The group relationship standing rule.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, GroupRelationshipStanding $group_relationship_standing) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->groupRelationshipStanding = $group_relationship_standing;
  }

  /**
   * Save groups for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to set the groups for.
   * @param array $groups_to_remove
   *   The ids of the groups the node must be removed from.
   * @param array $groups_to_add
   *   The ids of the groups the node must be added to.
   * @param array $original_groups
   *   (optional) The ids of the groups the node was in before this change.
   * @param bool $is_new
   *   (optional) TRUE when the node is being created, FALSE when it is being
   *   updated. It decides whether the members of every cross-posted group are
   *   notified, so only creation paths may pass TRUE.
   *
   * @return \Drupal\node\NodeInterface
   *   The node the groups were set for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setGroupsForNode(NodeInterface $node, array $groups_to_remove, array $groups_to_add, array $original_groups = [], bool $is_new = FALSE): NodeInterface {
    $moved = FALSE;

    // Keep in sync with SocialGroupReferenceItemList::postSave(): the callers
    // that reach this method delete group_content entities outside any entity
    // save, so that guard never sees them (PROD-36392).
    //
    // Filtering has to happen before the early return below, or a removal that
    // is entirely blocked still deletes the activity items of a move that
    // never happened.
    $groups_to_remove = array_diff(
      $groups_to_remove,
      $this->lockedGroupIds($node, $groups_to_remove),
    );

    // If we don't have to add or remove groups, we don't need to move anything.
    // the node is just updated for other values.
    if (empty($groups_to_add) && empty($groups_to_remove)) {
      return $node;
    }

    // Remove the notifications related to the node if a group is added or
    // moved.
    if ((empty($original_groups) || $original_groups != $groups_to_add)) {
      $entity_query = $this->entityTypeManager->getStorage('activity')->getQuery();
      $entity_query->accessCheck();
      $entity_query->condition('field_activity_entity.target_id', $node->id(), '=');
      $entity_query->condition('field_activity_entity.target_type', 'node', '=');

      // 1. From Group -> Community OR Group.
      // If there are original groups, it means content is removed from
      // inside a group. So we can remove the create_node-bundle_group
      // message from the streams.
      if (!empty($original_groups)) {
        $template = 'create_' . $node->bundle() . '_group';
        $messages = $this->entityTypeManager->getStorage('message')
          ->loadByProperties(['template' => $template]);

        // Make sure we have a message template to work with.
        if ($messages) {
          $entity_query->condition('field_activity_message.target_id', array_keys($messages), 'IN');
        }

        $moved = TRUE;
      }
      // 1. From Community -> GROUP
      // If there are no original groups, and there are groups we should add the
      // piece of content to it means content is placed from the community
      // in to a group and we remove the "create_node-bundle_community
      // message from the streams.
      elseif (empty($original_groups) && !empty($groups_to_add)) {
        $template = 'create_' . $node->bundle() . '_community';
        $messages = $this->entityTypeManager->getStorage('message')
          ->loadByProperties(['template' => $template]);

        // Make sure we have a message template to work with.
        if ($messages) {
          $entity_query->condition('field_activity_message.target_id', array_keys($messages), 'IN');
        }

        $moved = TRUE;
      }

      // Delete all activity items connected to our query.
      if (!empty($ids = $entity_query->execute())) {
        $controller = $this->entityTypeManager->getStorage('activity');
        $controller->delete($controller->loadMultiple($ids));
      }

      // Make sure to delete all activity items connected to the moved content
      // template.
      if ($moved) {
        $messages = $this->entityTypeManager->getStorage('message')
          ->loadByProperties(['template' => 'moved_content_between_groups']);

        // Make sure we have a message template to work with.
        if ($messages) {
          $entity_query->condition('field_activity_message.target_id', array_keys($messages), 'IN');
        }

        // Delete all activity items connected to our query.
        if (!empty($ids = $entity_query->execute())) {
          $controller = $this->entityTypeManager->getStorage('activity');
          $controller->delete($controller->loadMultiple($ids));
        }
      }
    }

    // Remove all the group content references from the Group as well if we
    // moved it out of the group.
    if (!empty($groups_to_remove)) {
      $groups = Group::loadMultiple($groups_to_remove);
      foreach ($groups as $group) {
        self::removeGroupRelationship($node, $group);
      }
    }

    // Add the content to the Group if we placed it in a group.
    if (!empty($groups_to_add)) {
      $groups = Group::loadMultiple($groups_to_add);
      // Adding a relationship saves it synchronously, so the activity entity
      // conditions run inside this loop. Tell them whether the content is
      // becoming visible in its groups for the first time, so that they can
      // notify the members of every cross-posted group on creation but stay
      // silent when a later edit adds another group. The signal is the $is_new
      // argument, which the GraphQL createTopic and createEvent mutations pass
      // as TRUE. Those mutations depend on this loop: they create the
      // relationships here, before the "groups" field is ever set. The node
      // add form reaches this loop too, with $is_new TRUE and a populated
      // $groups_to_add, but every iteration is a no-op there:
      // SocialGroupReferenceItemList::postSave() already created the
      // relationships earlier in the same request, and addGroupRelationship()
      // skips groups the node is already in. That guard is what keeps the add
      // form from creating a second relationship, and so a second
      // notification.
      $previously_raised = FirstPublicationMarker::isRaised();
      FirstPublicationMarker::setRaised((bool) $is_new);

      try {
        foreach ($groups as $group) {
          self::addGroupRelationship($node, $group);
        }
      }
      finally {
        FirstPublicationMarker::setRaised($previously_raised);
      }
    }

    // Invoke hook_social_group_move if the content is not new.
    if ($moved && !$is_new) {
      $this->moduleHandler->invokeAll("social_group_move", [$node]);
    }

    return $node;
  }

  /**
   * Gets the group ids, among those requested, this account may not detach.
   *
   * Callers that report errors to a client should ask this first and surface a
   * violation: setGroupsForNode() drops the refused ids from the removal set,
   * so a caller that does not ask gets a silent partial no-op. The rule itself
   * is owned by \Drupal\social_group\GroupRelationshipStanding.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose group relationships are being reconciled.
   * @param array $group_ids
   *   The group ids the caller asks to detach.
   *
   * @return array<int|string, int|string>
   *   The subset of $group_ids that will be refused, keyed by group id.
   */
  public function lockedGroupIds(NodeInterface $node, array $group_ids): array {
    if (empty($group_ids)) {
      return [];
    }

    $relationships = [];

    foreach (GroupRelationship::loadByEntity($node) as $relationship) {
      $relationships[$relationship->getGroupId()] = $relationship;
    }

    return array_intersect(
      $group_ids,
      $this->groupRelationshipStanding->lockedGroupIds($node, $relationships),
    );
  }

  /**
   * Adds the node to a group by creating a group_content (relationship) entity.
   *
   * Adds only if the node is not already in this group. That way the node can
   * be added to multiple groups in one pass regardless of iteration order.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to add to the group.
   * @param \Drupal\group\Entity\Group $group
   *   The group to add the node to.
   */
  public static function addGroupRelationship(NodeInterface $node, Group $group): void {
    if (self::isNodeInGroup($node, $group)) {
      return;
    }
    // @todo Check if group plugin id exists.
    $plugin_id = 'group_node:' . $node->bundle();
    $group->addRelationship($node, $plugin_id, ['uid' => $node->getOwnerId()]);
  }

  /**
   * Checks whether the node has a group_content relationship to the group.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param \Drupal\group\Entity\Group $group
   *   The group.
   *
   * @return bool
   *   TRUE if the node is in the group, FALSE otherwise.
   */
  private static function isNodeInGroup(NodeInterface $node, Group $group): bool {
    $group_id = $group->id();
    $relationships = GroupRelationship::loadByEntity($node);
    foreach ($relationships as $relationship) {
      if ($relationship->getGroup()->id() === $group_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Deletes a group relationship.
   *
   * Unguarded primitive: it deletes whatever it is handed. The standing rule -
   * "an account cannot detach a group it was never able to act on" - is applied
   * by setGroupsForNode(), the only caller, which filters $groups_to_remove
   * before it gets here. Anything reaching this method directly bypasses that
   * protection, so route new callers through setGroupsForNode() instead.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Object of a node.
   * @param \Drupal\group\Entity\Group $group
   *   Object of a group.
   */
  public static function removeGroupRelationship(NodeInterface $node, Group $group): void {
    // Try to load group content from entity.
    $group_contents = GroupRelationship::loadByEntity($node);
    foreach ($group_contents as $group_content) {
      if ($group->id() === $group_content->getGroup()->id()) {
        $group_content->delete();
      }
    }
  }

}
