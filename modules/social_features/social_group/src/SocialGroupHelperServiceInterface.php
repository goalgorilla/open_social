<?php

namespace Drupal\social_group;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines the helper service interface.
 *
 * @package Drupal\social_group
 */
interface SocialGroupHelperServiceInterface {

  /**
   * SocialGroupHelperService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    Connection $connection,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $translation,
    EntityTypeManagerInterface $entity_type_manager
  );

  /**
   * Returns a group id from a entity (post, node).
   *
   * @param array $entity
   *   The entity in the form of an entity reference array to get the group for.
   * @param bool $read_cache
   *   Whether the per-request cache should be used. This should only be
   *   disabled if you know that the group for the entity has changed because
   *   disabling this can have serious performance implications. Setting this to
   *   FALSE will update the cache for subsequent calls.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group that this entity belongs to or NULL if the entity doesn't
   *   belong to any group.
   */
  public function getGroupFromEntity(array $entity, bool $read_cache = TRUE);

  /**
   * Returns the default visibility.
   *
   * @param string $type
   *   The Group Type.
   *
   * @return string|null
   *   The default visibility.
   */
  public static function getDefaultGroupVisibility(string $type);

  /**
   * Returns the statically cached group members form the current group.
   *
   * @return array
   *   All group members as array with value user->id().
   */
  public static function getCurrentGroupMembers();

  /**
   * Get all group memberships for a certain user.
   *
   * @param int $uid
   *   The UID for which we fetch the groups it is member of.
   *
   * @return array
   *   List of group IDs the user is member of.
   */
  public function getAllGroupsForUser(int $uid);

  /**
   * Count all group memberships for a certain user.
   *
   * @param string $uid
   *   The UID for which we fetch the groups it is member of.
   *
   * @return int
   *   Count of groups a user is a member of.
   */
  public function countGroupMembershipsForUser(string $uid): int;

  /**
   * Get the add group URL for given user.
   *
   * This returns either /group/add or /group/add/{group_type}
   * depending upon the permission of the user to create group.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Url|null
   *   URL of the group add page.
   */
  public function getGroupsToAddUrl(AccountInterface $account);

  /**
   * Provides a field for potential members.
   *
   * @return array
   *   The renderable field.
   */
  public function addMemberFormField();

}
