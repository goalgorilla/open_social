<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
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
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentManager;

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
   * Constructs a GroupContentMultipleActivityEntityCondition object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_content_plugin_manager
   *   The group content enabler manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, GroupContentEnablerManagerInterface $group_content_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStorage = $entity_type_manager->getStorage('group');
    $this->database = $database;
    $this->groupContentManager = $group_content_plugin_manager;
  }

  /**
   * Get groups which have a current node as a group content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupIdsForNode(NodeInterface $node): array {
    $validPlugins = $this->getValidGroupContentPluginIds();

    $query = $this->database->select('group_content_field_data', 'gc');
    $query->addField('gc', 'gid');
    $query->condition('gc.entity_id', $node->id());
    $query->condition('gc.type', $validPlugins, 'IN');

    $gids = $query->execute()->fetchAllKeyed(0, 0);

    if ($gids) {
      // Check access to groups.
      $gids = $this->groupStorage->getQuery()
        ->condition('id', $gids, 'IN')
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
   * @param \Drupal\group\Entity\GroupContentInterface $groupContent
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupIdsByGroupContentNodeEntity(GroupContentInterface $groupContent): array {
    $validPlugins = $this->getValidGroupContentPluginIds();
    // If group content is not a node - nothing return.
    if (!$validPlugins) {
      return [];
    }

    // Get node id.
    $subQuery = $this->database->select('group_content_field_data', 'gc');
    $subQuery->addField('gc', 'entity_id');
    $subQuery->condition('gc.id', $groupContent->id());

    // Get count of group content with the current node.
    $query = $this->database->select('group_content_field_data', 'gc');
    $query->addField('gc', 'gid');
    $query->condition('gc.entity_id', $subQuery);
    $query->condition('gc.type', $validPlugins, 'IN');

    $gids = $query->execute()->fetchAllKeyed(0, 0);

    if ($gids) {
      // Check access to groups.
      $gids = $this->groupStorage->getQuery()
        ->condition('id', $gids, 'IN')
        ->execute();
    }

    return $gids ?: [];
  }

  /**
   * Returns groups list have the same group content entity (node).
   *
   * @param \Drupal\group\Entity\GroupContentInterface $groupContent
   *   Node object.
   *
   * @return array
   *   An array with group ids.
   */
  public function getGroupsByGroupContentNodeEntity(GroupContentInterface $groupContent): array {
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
    elseif ($entity instanceof GroupContentInterface) {
      $gids = $this->getGroupIdsByGroupContentNodeEntity($entity);
    }

    return isset($gids) ? count($gids) : 0;
  }

  /**
   * Returns flag if node exists in multiple groups.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Returns flag if node exists in multiple groups.
   */
  public function nodeExistsInMultipleGroups(ContentEntityInterface $entity): bool {
    return $this->countGroupsByGroupContentNode($entity) > 1;
  }

  /**
   * Returns existed group content plugins applicable to nodes.
   *
   * @return array
   *   An array with plugin ids.
   */
  public function getValidGroupContentPluginIds(): array {
    $groupContentPluginIds = array_filter($this->groupContentManager->getInstalledIds(), function ($string) {
      return strpos($string, 'group_node:') === 0;
    });

    $plugins = [];
    foreach ($groupContentPluginIds as $pluginId) {
      $plugins = array_merge($plugins, $this->groupContentManager->getGroupContentTypeIds($pluginId));
    }

    return $plugins;
  }

}
