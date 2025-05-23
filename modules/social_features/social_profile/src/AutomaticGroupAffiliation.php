<?php

namespace Drupal\social_profile;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\user\UserInterface;

/**
 * Automatic group affiliation service.
 *
 * This service manage values of profile's field "field_group_affiliation".
 *
 * There are three states of affiliation:
 *  - user-owned affiliation
 *  - system-added affiliation
 *  - user-removed affiliation
 *
 * A user-owned affiliation is one that has been explicitly managed by the user
 * (added, reordered, or otherwise modified) and is considered reviewed and
 * approved. A system-added affiliation is automatically suggested by the
 * platform and has not been modified by the user.
 *
 * Once an affiliation becomes user-owned, it cannot revert to being
 * system-added. However, a system-added affiliation can become user-owned
 * through user interaction.
 *
 * If a user removes a system-added affiliation, it is stored in the list of
 * user removed affiliations (profile base field user_removed_affiliations) to
 * ensure it is not automatically re-added, respecting the user's decision.
 * The user can manually re-add these affiliations if desired.
 *
 * The order of affiliations is meaningful. User-owned affiliations always
 * appear first in the order defined by the user. System-added affiliations
 * appear below, and their order is determined by platform configuration,
 * when multiple system affiliations exist.
 *
 * By default, automatic system-added (and also user-removed) group affiliations
 * are disabled. Automatic group affiliations are controlled by platform
 * configuration provided by
 * social_profile.automatic_group_affiliations.{profile_type} config.
 */
class AutomaticGroupAffiliation {

  // Indicates how many items in the list are user-owned. Items from the
  // beginning of the list up to this number are considered owned by the user.
  // Items beyond this threshold are system-added. A value of -1 means the user
  // has not yet managed the list, and all items are currently system-added.
  const string AFFILIATION_OWNED_COUNT_FILED_NAME = 'affiliation_owned_count';

  // Default value of -1 means the user has not yet managed the list, and all
  // items are currently system-added.
  const int DEFAULT_AFFILIATION_OWNED_COUNT_VALUE = -1;

  // A list of affiliations the user has manually removed from their profile.
  // These entries are retained to prevent the system from automatically
  // re-adding them, ensuring the user's preferences are respected.
  const string USER_REMOVED_AFFILIATIONS_FILED_NAME = 'user_removed_affiliations';

  /**
   * User profile.
   *
   * @var \Drupal\social_profile\Entity\ProfileAffiliationInterface
   *   Profile affiliation interface.
   */
  private ProfileAffiliationInterface $userProfile;

  /**
   * User entity.
   *
   * @var \Drupal\user\UserInterface
   *   User interface.
   */
  private UserInterface $user;

  /**
   * Subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param GroupAffiliation $groupAffiliation
   *   The group affiliation.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected CacheBackendInterface $cacheBackend,
    protected RouteMatchInterface $routeMatch,
    protected GroupAffiliation $groupAffiliation,
  ) {}

  /**
   * Updates user affiliations when changes are made by the user.
   *
   * This method compares the current and original user profile affiliations to
   * determine which affiliations have been added or removed by the user.
   * It updates the user-removed affiliations accordingly and refreshes the
   * count of user-owned affiliations.
   *
   * Early returns occur if the automatic affiliation feature is disabled for
   * the profile bundle, if the change is not made by editing profile form or
   * if there are no changes in the affiliations.
   *
   * @param \Drupal\social_profile\Entity\ProfileAffiliationInterface $user_profile
   *   The user profile entity with affiliations.
   *
   * @return void
   *   Return void.
   */
  public function updateUserAffiliationsByUser(ProfileAffiliationInterface $user_profile): void {
    // Early return if conditions for automatic affiliations are not fulfilled.
    if (!$this->isAutomaticGroupAffiliationFeatureEnabled($user_profile)) {
      return;
    }

    // Early return if the user profile was not updated via the edit form.
    // The user entity can also be changed through automated processes such as:
    // - Automatic updates (e.g., modifying roles or memberships),
    // - Programmatic saves (e.g., via custom code or contributed modules).
    // In such cases, the route won't match the edit form, so we skip logic
    // meant only for manual user profile edits.
    if ($this->routeMatch->getRouteName() !== 'profile.user_page.single') {

      return;
    }

    $this->setUserProfile($user_profile);

    /** @var \Drupal\social_profile\Entity\ProfileAffiliationInterface $original */
    $original = $this->entityTypeManager
      ->getStorage('profile')
      ->loadUnchanged($this->userProfile->id());

    // Early return if the user's platform affiliations have not changed.
    // Note: Changes in the order of affiliations are also considered changes.
    if (
      $this->userProfile->get($this->groupAffiliation::AFFILIATION_FIELD_NAME)->getValue() ===
      $original->get($this->groupAffiliation::AFFILIATION_FIELD_NAME)->getValue()
    ) {

      return;
    }

    // Check if user removed any affiliations and add them to user-removed
    // affiliations field.
    $newly_removed_affiliations = $this->getNewlyRemovedAffiliationsByUser();
    $user_profile->appendUserRemovedAffiliationGroupIds($newly_removed_affiliations);

    // Check if user added any affiliations and remove them from user-removed
    // affiliations field.
    $newly_added_affiliations = $this->getNewlyAddedAffiliationsByUser();
    $user_profile->removeUserRemovedAffiliationGroupIds($newly_added_affiliations);

    // Update the count of user-owned affiliations to reflect
    // the total number of affiliations currently set on the user profile.
    $all_user_affiliations = $user_profile->getAllUserAffiliationGroupIds();
    $user_profile->setUserOwnedAffiliationsCount(count($all_user_affiliations));
  }

  /**
   * Updates user affiliations when a new user group membership is created.
   *
   * When a new group membership is created for a user, this method finds all
   * their profiles eligible for affiliation and updates the system-added
   * affiliations based on the new membership. This includes re-calculating
   * the positions of system-added affiliations, while keeping user-owned
   * affiliations and their count unchanged.
   *
   * @param \Drupal\group\Entity\GroupMembership $group_membership
   *   The group membership entity that was created.
   *
   * @return void
   *   Return void.
   */
  public function userMembershipIsCreated(GroupMembership $group_membership): void {
    /** @var \Drupal\user\Entity\User $user */
    $user = $group_membership->getEntity();

    /** @var \Drupal\profile\Entity\ProfileInterface $user_profiles */
    $user_profiles = $this->entityTypeManager
      ->getStorage('profile')
      ->loadByProperties(['uid' => $user->id()]);

    /** @var \Drupal\profile\Entity\ProfileInterface $user_profile */
    foreach ($user_profiles as $user_profile) {
      // Early return if conditions for automatic affiliations are not
      // fulfilled.
      if (!$this->isAutomaticGroupAffiliationFeatureEnabled($user_profile)) {
        continue;
      }

      /** @var \Drupal\social_profile\Entity\ProfileAffiliationInterface $user_profile */
      $this->setUserProfile($user_profile);

      // Add affiliation to system added affiliations and re-calculate
      // positions for system added affiliations. Affiliation owned count
      // remains unchanged, as user-owned affiliations are not affected.
      $this->updateSystemAddedAffiliations();
    }
  }

  /**
   * Updates user affiliations when a user group membership is updated.
   *
   * When a user's group membership changes (e.g., role update), this method
   * loads all related user profiles eligible for affiliation. It then
   * recalculates system-added affiliations accordingly, respecting user-owned
   * affiliations and those the user has explicitly removed.
   *
   * The recalculation may add, remove, reorder, or leave affiliations
   * unchanged.
   *
   * @param \Drupal\group\Entity\GroupMembership $group_membership
   *   The group membership entity that was updated.
   *
   * @return void
   *   Returns void.
   */
  public function userMembershipIsUpdated(GroupMembership $group_membership): void {
    /** @var \Drupal\user\Entity\User $user */
    $user = $group_membership->getEntity();

    $user_profiles = $this->entityTypeManager
      ->getStorage('profile')
      ->loadByProperties(['uid' => $user->id()]);

    /** @var \Drupal\profile\Entity\ProfileInterface $user_profile */
    foreach ($user_profiles as $user_profile) {
      // Early return if conditions for automatic affiliations are not
      // fulfilled.
      if (!$this->isAutomaticGroupAffiliationFeatureEnabled($user_profile)) {
        continue;
      }

      /** @var \Drupal\social_profile\Entity\ProfileAffiliationInterface $user_profile */
      $this->setUserProfile($user_profile);
      $affiliation_group_id = $group_membership->getGroupId();

      // User-owned affiliations are not affected by membership changes
      // respecting the user's choices takes priority over automatic updates.
      // (They are only affected when a membership is removed, which is
      // handled separately by the userMembershipIsDeleted() method.)
      if ($user_profile->isAffiliationUserOwned($affiliation_group_id)) {
        continue;
      }

      // Skip processing if the user has explicitly removed this affiliation.
      // Respecting the user's choices takes priority over automatic updates,
      // preventing re-adding affiliations the user chose to remove.
      if ($user_profile->isAffiliationUserRemoved($affiliation_group_id)) {
        continue;
      }

      // Whether the affiliation was previously system-added or not is
      // irrelevant. For example, a membership's old role might not have
      // qualified for automatic affiliation, but the new role might. It's
      // important to re-calculate all system-added affiliations regardless of
      // their previous state. The result of the re-calculation may be that an
      // affiliation is added, removed, its position/order is updated, or
      // nothing changes.
      $this->updateSystemAddedAffiliations();
    }
  }

  /**
   * Updates user affiliations when a user group membership is deleted.
   *
   * When a user's group membership is deleted (e.g., user leaves or is removed
   * from a group), this method loads all related user profiles eligible for
   * affiliation. It then removes the corresponding group ID from the user
   * affiliations, updating user-owned or system-added affiliations accordingly.
   *
   * If the affiliation is user-owned, it is removed and the user-owned counter
   * is decremented. If the affiliation is system-added, it is removed without
   * affecting the user-owned counter. If the affiliation was previously marked
   * as removed by the user, that mark is also cleared.
   *
   * @param \Drupal\group\Entity\GroupMembership $group_membership
   *   The group membership entity that was deleted.
   *
   * @return void
   *   Returns void.
   */
  public function userMembershipIsDeleted(GroupMembership $group_membership): void {
    /** @var \Drupal\user\Entity\User $user */
    $user = $group_membership->getEntity();

    $user_profiles = $this->entityTypeManager
      ->getStorage('profile')
      ->loadByProperties(['uid' => $user->id()]);

    /** @var \Drupal\profile\Entity\ProfileInterface $user_profile */
    foreach ($user_profiles as $user_profile) {
      // Early return if conditions for automatic affiliations are not
      // fulfilled.
      if (!$this->isAutomaticGroupAffiliationFeatureEnabled($user_profile)) {
        continue;
      }

      /** @var \Drupal\social_profile\Entity\ProfileAffiliationInterface $user_profile */
      $this->setUserProfile($user_profile);
      $affiliation_group_id = $group_membership->getGroupId();

      // If affiliation is user-owned, remove it from the user affiliations
      // and reduce the user-owned counter by 1.
      if ($this->userProfile->isAffiliationUserOwned($affiliation_group_id)) {
        $this->userProfile->removeUserAffiliationGroupIds([$affiliation_group_id]);
        $current_user_owned_count = $this->userProfile->getUserOwnedAffiliationsCount();
        $this->userProfile->setUserOwnedAffiliationsCount($current_user_owned_count - 1);
        $this->userProfile->save();
      }

      // If affiliation is system-added, remove it from the user affiliations
      // and do not change user-owned counter.
      elseif ($this->userProfile->isAffiliationSystemAdded($affiliation_group_id)) {
        $this->userProfile->removeUserAffiliationGroupIds([$affiliation_group_id]);
        $this->userProfile->save();

        // If affiliation is system-added, remove it from the user affiliations
        // and do not change user-owned counter.
      }
      elseif ($this->userProfile->isAffiliationUserRemoved($affiliation_group_id)) {
        $this->userProfile->removeUserRemovedAffiliationGroupIds([$affiliation_group_id]);
        $this->userProfile->save();
      }

      // Note: Deleted user membership is not an affiliation, no further steps
      // are needed.
    }
  }

  /**
   * Checks if automatic group affiliation is enabled for a given profile type.
   *
   * The feature is enabled only if all the following conditions are met:
   * - The global affiliation feature is enabled on the platform.
   * - The profile type implements ProfileAffiliationInterface.
   * - The profile type has the required affiliation fields.
   * - The profile type has at least one automatic affiliation rule defined.
   *
   * Method cache_id:
   *   automatic_affiliation_feature_enabled_{profile_type}
   *
   * Cache tags:
   *   group_affiliation_options_by_user
   *   config:profile.type.{profile_type}
   *   config:social_profile.automatic_group_affiliations.{profile_type},
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   User profile to check.
   *
   * @return bool
   *   TRUE if the automatic group affiliation feature is enabled,
   *   FALSE otherwise.
   */
  private function isAutomaticGroupAffiliationFeatureEnabled(ProfileInterface $profile): bool {
    $profile_type = $profile->bundle();
    $cache_id = 'automatic_affiliation_feature_enabled_' . $profile_type;
    $cache = $this->cacheBackend->get($cache_id);

    if ($cache !== FALSE) {
      return $cache->data;
    }

    $result =
      // The global affiliation feature is enabled on the platform.
      $this->groupAffiliation->isAffiliationFeatureEnabled() &&
      // The profile type implements ProfileAffiliationInterface.
      $profile instanceof ProfileAffiliationInterface &&
      // The profile type has the required affiliation fields.
      $profile->profileBundleHasAffiliations() &&
      // The profile type has at least one automatic affiliation rule defined.
      !empty($this->getAutomaticGroupAffiliationRules($profile_type));

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([
        $this->groupAffiliation::GENERAL_CACHE_TAG,
        'config:profile.type.' . $profile_type,
        'config:social_profile.automatic_group_affiliations.' . $profile_type,
      ]);

    $this->cacheBackend->set($cache_id, $result, Cache::PERMANENT, $cacheability->getCacheTags());

    return $result;
  }

  /**
   * Sets the user profile and user properties.
   *
   * This allows reuse of these properties within the service.
   *
   * @param \Drupal\social_profile\Entity\ProfileAffiliationInterface $user_profile
   *   The user profile entity with affiliations.
   *
   * @return void
   *   Return void.
   */
  private function setUserProfile(ProfileAffiliationInterface $user_profile): void {
    $this->userProfile = $user_profile;
    $this->user = $user_profile->getOwner();
  }

  /**
   * Gets the user memberships base on rules.
   *
   * This method is inspired by
   * Drupal\group\Entity\GroupMembershipTrait::loadByUser().
   *
   * Note this method does not filter out user-removed affiliations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the memberships for.
   * @param array $rule_configuration
   *   An associative array containing the rule configuration with the
   *   following structure:
   *   - weight: int
   *     The rule's weight for ordering.
   *   - sort: array
   *     An array with sorting information:
   *     - by: string
   *       The field to sort by.
   *     - direction: string
   *       The sort direction ('ASC' or 'DESC').
   *   - selectors: array
   *     Criteria to filter memberships:
   *     - type: string
   *       The group type ID.
   *     - role: string
   *       The group role ID.
   *
   * @return array<int, GroupMembership>
   *   An array of GroupMembership objects indexed by their IDs
   */
  private function getUserMemberships(AccountInterface $account, array $rule_configuration): array {
    $affiliation_enabled_group_types = $this->groupAffiliation->getAffiliationEnabledGroupTypes();
    $storage = $this->entityTypeManager->getStorage('group_content');
    // Flag to check if $configuration has at least one condition that matches
    // with enabled affiliation group type.
    $allowed_group_types = FALSE;

    $query = $storage->getQuery();
    $query->condition('entity_id', $account->id());
    $query->condition('plugin_id', 'group_membership');
    $query->condition('gid.entity.status', 1);

    // Process conditions.
    $or_group = $query->orConditionGroup();
    foreach ($rule_configuration['selectors'] as $selector) {
      // Process only group types that have affiliation enabled.
      if (in_array($selector['type'], array_keys($affiliation_enabled_group_types), TRUE)) {
        $and_group = $query->andConditionGroup()
          ->condition('group_type', $selector['type'])
          ->condition('group_roles', $selector['role']);
        $or_group->condition($and_group);
        $allowed_group_types = TRUE;
      }
    }
    $query->condition($or_group);

    // Early return if affiliation is not enabled for any of group types defined
    // in conditions.
    if (!$allowed_group_types) {
      return [];
    }

    // Sort by group membership property (example: group_membership->created)
    // and not by group (example: group->created).
    if (isset($rule_configuration['sort']['by']) && isset($rule_configuration['sort']['direction'])) {
      $query->sort($rule_configuration['sort']['by'], $rule_configuration['sort']['direction']);
    }

    $query->accessCheck(FALSE);
    $membership_ids = $query->execute();

    /** @var \Drupal\group\Entity\GroupMembership[] $memberships */
    $memberships = $storage->loadMultiple($membership_ids);

    return $memberships;
  }

  /**
   * Return groups from group memberships.
   *
   * @param array<int,GroupMembership> $group_memberships
   *   An array of group membership entities.
   *
   * @return array<int,int>
   *   Returns membership id as key and group id as value.
   */
  private function getGroupIdsFromGroupMemberships(array $group_memberships): array {
    $groups = [];
    foreach ($group_memberships as $group_membership) {
      $groups[(int) $group_membership->id()] = (int) $group_membership->getGroupId();
    }

    return $groups;
  }

  /**
   * Get automatic group affiliation rules.
   *
   * Automatic group affiliation rules define the conditions and order in which
   * automatic group affiliations are provided.
   *
   * Method cache_id:
   *   automatic_group_affiliation_rules_{bundle}.
   *
   * @param string $profile_type
   *   Machine name of profile type to get automatic affiliation rules for.
   *
   * @return array
   *   Automatic affiliation rules.
   */
  private function getAutomaticGroupAffiliationRules(string $profile_type): array {
    $cache_id = 'automatic_group_affiliation_rules_' . $profile_type;
    $cache = $this->cacheBackend->get($cache_id);

    if ($cache !== FALSE) {
      return $cache->data;
    }

    $config = $this->configFactory->get('social_profile.automatic_group_affiliations.' . $profile_type);
    $rules = $config->get('rules') ?? [];

    // Sort rules by weight in case properties are not listed by weight order.
    usort($rules, fn($a, $b) => $a['weight'] <=> $b['weight']);

    $this->cacheBackend->set($cache_id, $rules, Cache::PERMANENT);

    return $rules;
  }

  /**
   * Recalculates system-added group affiliations based on defined rules.
   *
   * This method processes all automatic affiliation rules for the user's
   * profile type, applies them in order, and collects the resulting group IDs.
   * It:
   *  - Merges group IDs from all matching rules, prioritizing earlier rules.
   *  - Removes any user-owned affiliation group IDs.
   *  - Removes any user-removed affiliation group IDs.
   *  - Ensures all returned IDs are integers.
   *
   * @return array<int, int>
   *   A list of group IDs to be assigned as system-added affiliations.
   */
  private function reCalculateSystemAddedAffiliations(): array {
    $affiliation_group_ids = [];
    $rules = $this->getAutomaticGroupAffiliationRules($this->userProfile->bundle());

    foreach ($rules as $rule) {
      $user_memberships_per_rule = $this->getUserMemberships($this->user, $rule);
      $affiliated_group_ids_per_rule = $this->getGroupIdsFromGroupMemberships($user_memberships_per_rule);
      // Remove duplicates (higher rule has priority over lower rule)
      $affiliation_group_ids = array_unique(array_merge($affiliation_group_ids, $affiliated_group_ids_per_rule));
    }

    // Remove user-owned affiliations.
    $affiliation_group_ids = array_values(array_diff($affiliation_group_ids, $this->userProfile->getUserOwnedAffiliationGroupIds()));

    // Remove user-removed affiliations.
    $affiliation_group_ids = array_values(array_diff($affiliation_group_ids, $this->userProfile->getUserRemovedAffiliationGroupIds()));

    // Make sure that values are correctly cast.
    return array_map('intval', $affiliation_group_ids);
  }

  /**
   * Get newly removed user affiliation group IDs.
   *
   * Compares the current user affiliations with the original saved profile and
   * returns the group IDs that were removed by the user since the last save.
   *
   * @return array<int, int>
   *   An array of affiliation group IDs removed by the user.
   */
  private function getNewlyRemovedAffiliationsByUser(): array {
    if (!$this->userProfile->id()) {
      return [];
    }

    $original = $this->entityTypeManager
      ->getStorage('profile')
      ->loadUnchanged($this->userProfile->id());

    if (!$original instanceof ProfileAffiliationInterface) {
      return [];
    }

    $current = $this->userProfile->getAllUserAffiliationGroupIds();
    $original = $original->getAllUserAffiliationGroupIds();

    return array_diff($original, $current);
  }

  /**
   * Get newly added user affiliation group IDs.
   *
   * Compares the current user affiliations with the original saved profile
   * and returns the group IDs that were added by the user since the last save.
   *
   * @return array<int, int>
   *   An array of affiliation group IDs added by the user.
   */
  private function getNewlyAddedAffiliationsByUser(): array {
    if (!$this->userProfile->id()) {
      return [];
    }

    $original = $this->entityTypeManager
      ->getStorage('profile')
      ->loadUnchanged($this->userProfile->id());

    if (!$original instanceof ProfileAffiliationInterface) {
      return [];
    }

    $current = $this->userProfile->getAllUserAffiliationGroupIds();
    $original = $original->getAllUserAffiliationGroupIds();

    return array_diff($current, $original);
  }

  /**
   * Updates system-added affiliations.
   *
   * Recalculates system-added affiliations by adding new ones, removing
   * outdated ones, and adjusting their order as needed.
   *
   * @return void
   *   Return void
   */
  private function updateSystemAddedAffiliations(): void {
    $user_owned_affiliations = $this->userProfile->getUserOwnedAffiliationGroupIds();
    $new_system_added_affiliations = $this->reCalculateSystemAddedAffiliations();

    $this->userProfile->setAllUserAffiliationGroupIds(
      array_merge(
        $user_owned_affiliations,
        $new_system_added_affiliations,
      )
    );

    $this->userProfile->save();
  }

}
