<?php

namespace Drupal\social_group;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
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
  public static function setGroupsForNode(Node $node, array $groups_to_remove, array $groups_to_add) {
    if ($node->id()) {
      foreach ($groups_to_remove as $group_id) {
        $group = Group::load($group_id);
        self::removeGroupContent($node, $group);
      }
      foreach ($groups_to_add as $group_id) {
        $group = Group::load($group_id);
        self::addGroupContent($node, $group);
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
    // Get the group content plugin.
    $plugin_id = 'group_node:' . $node->bundle();
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $node->id(),
    ]);
    $group_content->save();
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
