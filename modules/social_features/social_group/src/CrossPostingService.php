<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a "CrossPostingService" service to handle cross-posting features.
 *
 * @package Drupal\social_group
 */
class CrossPostingService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager under test.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
   */
  protected $groupRelationTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Group storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupStorage;

  /**
   * Constructs a GroupRelationMultipleActivityEntityCondition object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, GroupRelationTypeManagerInterface $groupRelationTypeManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStorage = $entity_type_manager->getStorage('group');
    $this->database = $database;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
  }

  /**
   * Get groups which have a current node as a group relation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupIdsForNode(NodeInterface $node): array {
    $validPlugins = $this->getValidGroupRelationPluginIds();

    $query = $this->database->select('group_content_field_data', 'gc');
    $query->addField('gc', 'gid');
    $query->condition('gc.entity_id', $node->id());
    $query->condition('gc.type', $validPlugins, 'IN');

    $gids = $query->execute()->fetchAllKeyed(0, 0);

    if ($gids) {
      // Check access to groups.
      $gids = $this->groupStorage->getQuery()
        ->condition('id', $gids, 'IN')
        ->accessCheck()
        ->execute();
    }

    return $gids ?: [];
  }

  /**
   * Get groups entities which has current node as a group content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return array
   *   An array with groups entities.
   */
  public function getGroupsForNode(NodeInterface $node): array {
    $gids = $this->getGroupIdsForNode($node);

    return $this->groupStorage->loadMultiple($gids);
  }

  /**
   * Returns groups list have the same group content entity (node).
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $groupContent
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupIdsByGroupContentNodeEntity(GroupRelationshipInterface $groupContent): array {
    $validPlugins = $this->getValidGroupRelationPluginIds();
    // If group content is not a node - nothing return.
    if (!$validPlugins) {
      return [];
    }

    // Get node id.
    $subQuery = $this->database->select('group_relationship_field_data', 'gc');
    $subQuery->addField('gc', 'entity_id');
    $subQuery->condition('gc.id', $groupContent->id());

    // Get count of group content with the current node.
    $query = $this->database->select('group_relationship_field_data', 'gc');
    $query->addField('gc', 'gid');
    $query->condition('gc.entity_id', $subQuery);
    $query->condition('gc.type', $validPlugins, 'IN');

    $gids = $query->execute()->fetchAllKeyed(0, 0);

    if ($gids) {
      // Check access to groups.
      $gids = $this->groupStorage->getQuery()
        ->accessCheck()
        ->condition('id', $gids, 'IN')
        ->execute();
    }

    return $gids ?: [];
  }

  /**
   * Returns groups list have the same group content entity (node).
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $groupContent
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupsByGroupContentNodeEntity(GroupRelationshipInterface $groupContent): array {
    $gids = $this->getGroupIdsByGroupContentNodeEntity($groupContent);

    return $this->groupStorage->loadMultiple($gids);
  }

  /**
   * Returns number of groups where node is added as a content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @return int
   *   Returns groups count.
   */
  public function countGroupsByGroupContentNode(ContentEntityInterface $entity): int {
    if ($entity instanceof NodeInterface) {
      $gids = $this->getGroupIdsForNode($entity);
    }
    elseif ($entity instanceof GroupRelationshipInterface) {
      $gids = $this->getGroupIdsByGroupContentNodeEntity($entity);
    }

    return isset($gids) ? count($gids) : 0;
  }

  /**
   * Returns flag if node exists in multiple groups.
   *
   * @param \Drupal\Core\Entity\GroupRelationshipInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Returns flag if node exists in multiple groups.
   */
  public function nodeExistsInMultipleGroups(GroupRelationshipInterface $entity): bool {
    return $this->countGroupsByGroupContentNode($entity) > 1;
  }

  /**
   * Returns existed group relation plugins applicable to nodes.
   *
   * @return array
   *   An array with plugin ids.
   */
  public function getValidGroupRelationPluginIds(): array {
    $groupContentPluginIds = array_filter($this->groupRelationTypeManager->getAllInstalledIds(), function ($string) {
      return strpos($string, 'group_node:') === 0;
    });

    $plugins = [];
    foreach ($groupContentPluginIds as $pluginId) {
      $plugins = array_merge($plugins, $this->groupRelationTypeManager->getRelationshipTypeIds($pluginId));
    }

    return $plugins;
  }

}
