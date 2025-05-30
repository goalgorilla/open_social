<?php

namespace Drupal\social_profile\Entity\Bundle;

use Drupal\profile\Entity\Profile;
use Drupal\social_profile\AutomaticGroupAffiliation;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;

/**
 * Social profile bundle class with affiliations methods.
 */
final class SocialProfile extends Profile implements ProfileAffiliationInterface {

  /**
   * Indicates if the user manually changed affiliations during the request.
   *
   * This is a runtime-only flag and is not persisted across requests.
   * Useful for conditional logic during form submission or pre-save hooks.
   *
   * @var bool
   */
  protected bool $userModifiedAffiliations = FALSE;

  /**
   * {@inheritDoc}
   */
  public function profileBundleHasAffiliations(): bool {
    return (
      $this->hasField(GroupAffiliation::AFFILIATION_FIELD_NAME) &&
      $this->hasField(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME) &&
      $this->hasField(AutomaticGroupAffiliation::USER_REMOVED_AFFILIATIONS_FILED_NAME)
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getAllUserAffiliationGroupIds(): array {
    return array_map(
      'intval',
      array_column(
        $this->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->getValue(),
        'target_id'
      )
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getUserOwnedAffiliationsCount(): int {
    return !$this->get(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME)->isEmpty() ?
      (int) $this->{AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME}->value :
      AutomaticGroupAffiliation::DEFAULT_AFFILIATION_OWNED_COUNT_VALUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserOwnedAffiliationGroupIds(): array {
    $user_owned_affiliations_count = $this->getUserOwnedAffiliationsCount();

    // Default value of -1 means the user has not yet managed the list, and all
    // items are currently system-added.
    if ($user_owned_affiliations_count === AutomaticGroupAffiliation::DEFAULT_AFFILIATION_OWNED_COUNT_VALUE) {
      return [];
    }
    else {
      return array_slice($this->getAllUserAffiliationGroupIds(), 0, $user_owned_affiliations_count);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getSystemAddedAffiliationGroupIds(): array {
    $user_owned_affiliations_count = $this->getUserOwnedAffiliationsCount();

    // Default value of -1 means the user has not yet managed the list, and all
    // items are currently system-added.
    if ($user_owned_affiliations_count === AutomaticGroupAffiliation::DEFAULT_AFFILIATION_OWNED_COUNT_VALUE) {
      return $this->getAllUserAffiliationGroupIds();
    }
    else {
      return array_slice($this->getAllUserAffiliationGroupIds(), $user_owned_affiliations_count);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getUserRemovedAffiliationGroupIds(): array {
    return !$this->get(AutomaticGroupAffiliation::USER_REMOVED_AFFILIATIONS_FILED_NAME)->isEmpty() ?
      array_column($this->get(AutomaticGroupAffiliation::USER_REMOVED_AFFILIATIONS_FILED_NAME)->getValue(), 'target_id') :
      [];
  }

  /**
   * {@inheritDoc}
   */
  public function setAllUserAffiliationGroupIds(array $group_ids): void {
    $this->set(GroupAffiliation::AFFILIATION_FIELD_NAME, $group_ids);
  }

  /**
   * {@inheritDoc}
   */
  public function setUserOwnedAffiliationsCount(int $count): void {
    $this->set(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME, $count);
  }

  /**
   * {@inheritDoc}
   */
  public function setUserRemovedAffiliationGroupIds(array $group_ids): void {
    $this->set(AutomaticGroupAffiliation::USER_REMOVED_AFFILIATIONS_FILED_NAME, $group_ids);
  }

  /**
   * {@inheritDoc}
   */
  public function removeUserAffiliationGroupIds(array $group_ids): void {
    $current_ids = $this->getAllUserAffiliationGroupIds();
    $remaining_ids = array_diff($current_ids, $group_ids);
    $this->setAllUserAffiliationGroupIds(array_values($remaining_ids));
  }

  /**
   * {@inheritDoc}
   */
  public function appendUserRemovedAffiliationGroupIds(array $group_ids): void {
    if (!empty($group_ids)) {
      // Merge existing and new IDs, preserving unique values.
      $user_removed_affiliation_group_ids = array_unique(
        array_merge(
          $this->getUserRemovedAffiliationGroupIds(),
          $group_ids
        )
      );

      $this->setUserRemovedAffiliationGroupIds($user_removed_affiliation_group_ids);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function removeUserRemovedAffiliationGroupIds(array $group_ids): void {
    if (!empty($group_ids)) {
      $current = $this->getUserRemovedAffiliationGroupIds();
      $updated = array_diff($current, $group_ids);
      $this->setUserRemovedAffiliationGroupIds(array_values($updated));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function isAffiliation(int $group_id): bool {
    return in_array($group_id, $this->getAllUserAffiliationGroupIds());
  }

  /**
   * {@inheritDoc}
   */
  public function isAffiliationUserOwned(int $group_id): bool {
    return in_array($group_id, $this->getUserOwnedAffiliationGroupIds());
  }

  /**
   * {@inheritDoc}
   */
  public function isAffiliationSystemAdded(int $group_id): bool {
    return in_array($group_id, $this->getSystemAddedAffiliationGroupIds());
  }

  /**
   * {@inheritDoc}
   */
  public function isAffiliationUserRemoved(int $group_id): bool {
    return in_array($group_id, $this->getUserRemovedAffiliationGroupIds());
  }

  /**
   * {@inheritDoc}
   */
  public function markAffiliationsChangedByUser(): void {
    $this->userModifiedAffiliations = TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function hasUserModifiedAffiliations(): bool {
    return $this->userModifiedAffiliations;
  }

}
