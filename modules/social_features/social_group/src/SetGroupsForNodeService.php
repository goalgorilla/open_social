<?php

namespace Drupal\social_group;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;

/**
 * Class SetGroupsForNodeService.
 *
 * @package Drupal\social_group
 */
class SetGroupsForNodeService {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Save groups for a given node.
   */
  public static function setGroupsForNode(NodeInterface $node, array $groups_to_remove, array $groups_to_add, array $original_groups = []) {
    $moved = FALSE;

    // Remove the notifications related to the node if a group is added or
    // moved.
    if ((empty($original_groups) || $original_groups != $groups_to_add) && !empty($groups_to_add)) {
      $entity_query = \Drupal::entityQuery('activity');
      $entity_query->condition('field_activity_entity.target_id', $node->id(), '=');
      $entity_query->condition('field_activity_entity.target_type', 'node', '=');

      if (!empty($original_groups)) {
        $template = 'create_' . $node->bundle() . '_group';
        $messages = \Drupal::entityTypeManager()->getStorage('message')
          ->loadByProperties(['template' => $template]);
        $entity_query->condition('field_activity_message.target_id', array_keys($messages), 'IN');

        $moved = TRUE;
      }

      if (!empty($ids = $entity_query->execute())) {
        $controller = \Drupal::entityTypeManager()->getStorage('activity');
        $controller->delete($controller->loadMultiple($ids));
      }
    }

    foreach ($groups_to_remove as $group_id) {
      $group = Group::load($group_id);
      self::removeGroupContent($node, $group);
    }
    foreach ($groups_to_add as $group_id) {
      $group = Group::load($group_id);
      self::addGroupContent($node, $group);
    }

    if ($moved) {
      $hook = 'social_group_move';

      foreach (\Drupal::moduleHandler()->getImplementations($hook) as $module) {
        $function = $module . '_' . $hook;
        $function($node);
      }
    }

    return $node;
  }

  /**
   * Creates a group content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Object of a node.
   * @param \Drupal\group\Entity\Group $group
   *   Object of a group.
   */
  public static function addGroupContent(NodeInterface $node, Group $group) {
    // TODO Check if group plugin id exists.
    $plugin_id = 'group_node:' . $node->bundle();
    $group->addContent($node, $plugin_id, ['uid' => $node->getOwnerId()]);
  }

  /**
   * Deletes a group content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Object of a node.
   * @param \Drupal\group\Entity\Group $group
   *   Object of a group.
   */
  public static function removeGroupContent(NodeInterface $node, Group $group) {
    // Try to load group content from entity.
    if ($group_contents = GroupContent::loadByEntity($node)) {
      /* @var @param \Drupal\group\Entity\GroupContent $group_content */
      foreach ($group_contents as $group_content) {
        if ($group->id() === $group_content->getGroup()->id()) {
          $group_content->delete();
        }
      }
    }
  }

}
