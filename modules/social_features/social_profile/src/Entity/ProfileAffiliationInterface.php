<?php

namespace Drupal\social_profile\Entity;

use Drupal\profile\Entity\ProfileInterface;

/**
 * Profile affiliation interface.
 */
interface ProfileAffiliationInterface extends ProfileInterface {

  /**
   * Validates that profile bundle is eligible for affiliations.
   *
   * Make sure that user profile bundle is eligible for affiliations. At the
   * moment there is only one profile bundle, but it can happen in future that
   * we will have more profile bundles and not all of them will have affiliation
   * fields.
   *
   * @return bool
   *   Returns TRUE for profile bundle that has all required affiliation fields.
   */
  public function profileBundleHasAffiliations(): bool;

  /**
   * Get affiliation group IDs.
   *
   * Complete list of affiliations. This list does not distinguish between
   * user-owned and system-added affiliations.
   *
   * @return array<int, int>
   *   List of affiliation group IDs.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function getAllUserAffiliationGroupIds(): array;

  /**
   * Gets user owned affiliation count value.
   *
   * If the user owned affiliation count value is not defined, return default
   * value of -1, which means that the user has not yet managed the user group
   * affiliation list, and all items are currently system-added.
   *
   * @return int
   *   Returns user owned affiliation count value as integer.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function getUserOwnedAffiliationsCount(): int;

  /**
   * Gets the list of user-owned affiliation group IDs.
   *
   * This method returns the subset of the user’s affiliations that have been
   * explicitly added, reordered, or otherwise managed by the user. These are
   * considered reviewed and approved affiliations.
   *
   * @return array<int, int>
   *   List of user-owned affiliation group IDs.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function getUserOwnedAffiliationGroupIds(): array;

  /**
   * Gets the list of system-added affiliation group IDs.
   *
   * This method returns the subset of affiliations automatically assigned by
   * the platform based on rules or group memberships and that have not been
   * explicitly managed by the user. These affiliations appear after all
   * user-owned affiliations in the profile.
   *
   * @return array<int, int>
   *   List of system-added affiliation group IDs.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function getSystemAddedAffiliationGroupIds(): array;

  /**
   * Get user-removed affiliation group IDs.
   *
   * User-removed affiliations represent groups that were previously
   * system-added but have been manually removed by the user. These affiliations
   * are stored separately to prevent them from being automatically re-added by
   * the system, respecting the user's decision.
   *
   * @return array<int, int>
   *   List of user-removed affiliation group IDs.
   *
   * @see AutomaticGroupAffiliation
   *  See class description to learn more about user-removed affiliations.
   */
  public function getUserRemovedAffiliationGroupIds(): array;

  /**
   * Set all user affiliation group IDs.
   *
   * Replaces the current list of affiliation group IDs with the provided list.
   * This method does not distinguish between user-owned and system-added
   * affiliations—it sets the full affiliation list as-is.
   *
   * @param array<int, int> $group_ids
   *   An array of affiliation group IDs to assign to the profile.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function setAllUserAffiliationGroupIds(array $group_ids): void;

  /**
   * Sets the number of user-owned affiliations.
   *
   * @param int $count
   *   The number of user-owned affiliation group IDs.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function setUserOwnedAffiliationsCount(int $count): void;

  /**
   * Set user-removed affiliations.
   *
   * @param array $group_ids
   *   An array of user-removed affiliation group IDs.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function setUserRemovedAffiliationGroupIds(array $group_ids): void;

  /**
   * Remove affiliation group IDs.
   *
   * Remove affiliation group IDs from the user affiliations. This removal does
   * not distinguish between user-owned and system-added affiliations.
   *
   * @param array $group_ids
   *   An array of affiliation group IDs to remove from the user affiliations.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function removeUserAffiliationGroupIds(array $group_ids): void;

  /**
   * Append additional user-removed affiliation group IDs.
   *
   * @param array<int, int> $group_ids
   *   An array of affiliation group IDs to add to the user-removed affiliations
   *   list.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function appendUserRemovedAffiliationGroupIds(array $group_ids): void;

  /**
   * Remove specified user-removed affiliation group IDs.
   *
   * @param array<int, int> $group_ids
   *   An array of affiliation group IDs to remove from the user-removed
   *   affiliations list.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function removeUserRemovedAffiliationGroupIds(array $group_ids): void;

  /**
   * Checks if the given group ID is listed as an affiliation.
   *
   * This method returns TRUE if the group is present in the user's current
   * affiliation list, regardless of whether it is user-owned or system-added.
   *
   * @param int $group_id
   *   The ID of the group to check.
   *
   * @return bool
   *   TRUE if the group ID is part of the user's affiliation list,
   *   FALSE otherwise.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function isAffiliation(int $group_id): bool;

  /**
   * Determines if the given group ID represents a user-owned affiliation.
   *
   * User-owned affiliations are those explicitly created or selected by the
   * user, and should not be modified by automated processes such as membership
   * updates.
   *
   * @param int $group_id
   *   The ID of the group to check.
   *
   * @return bool
   *   Returns TRUE if the given group ID corresponds to a user-owned
   *   affiliation, FALSE otherwise.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function isAffiliationUserOwned(int $group_id): bool;

  /**
   * Determines if the given group ID represents a system-added affiliation.
   *
   * System-added affiliations are those automatically assigned by the platform
   * and have not been explicitly modified by the user. These affiliations can
   * be added or removed by automated processes based on group memberships and
   * platform configuration.
   *
   * @param int $group_id
   *   The ID of the group to check.
   *
   * @return bool
   *   Returns TRUE if the given group ID corresponds to a system-added
   *   affiliation, FALSE otherwise.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function isAffiliationSystemAdded(int $group_id): bool;

  /**
   * Determines if the given group ID represents a user-removed affiliation.
   *
   * This method checks whether the user has explicitly removed the affiliation
   * identified by the given group ID. It does not consider whether the
   * affiliation was originally added by the user or by the system.
   *
   * @param int $group_id
   *   The ID of the group to check.
   *
   * @return bool
   *   Returns TRUE if the affiliation with the given group ID was removed
   *   by the user. Returns FALSE otherwise.
   *
   * @see AutomaticGroupAffiliation
   *   See class description to learn more about automatic affiliations.
   */
  public function isAffiliationUserRemoved(int $group_id): bool;

}
