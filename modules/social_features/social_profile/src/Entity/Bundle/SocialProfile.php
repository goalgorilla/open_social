<?php

namespace Drupal\social_profile\Entity\Bundle;

use Drupal\Component\Render\MarkupInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
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
  public function getAllUserAffiliationGroupLabels(): array {
    // Use a static cache to avoid re-calculating the method results.
    // It's cached per object instance.
    $labels = &drupal_static(__METHOD__ . ':' . $this->uuid());
    if (is_array($labels)) {
      return $labels;
    }

    $ids = $this->getAllUserAffiliationGroupIds();
    if (empty($ids)) {
      return $labels = [];
    }

    $groups = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadMultiple($ids);

    $labels = [];
    foreach ($groups as $group) {
      // Get the translation of the group.
      $group = \Drupal::service('entity.repository')
        ->getTranslationFromContext($group);

      if (!$group->access('view')) {
        continue;
      }

      $labels[] = $group->label();
    }

    return $labels;
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

  /**
   * {@inheritDoc}
   */
  public function getPrimaryAffiliation(): array {
    // Use a static cache to avoid re-calculating the affiliation.
    // It's cached per object instance.
    $affiliation = &drupal_static(__METHOD__ . ':' . $this->uuid());
    if (isset($affiliation)) {
      return $affiliation;
    }

    // Make sure the affiliation feature is enabled globally.
    if (!\Drupal::service('social_profile.group_affiliation')
      ->isAffiliationFeatureEnabled()
    ) {
      return $affiliation = [];
    }

    // 1. Read the affiliation function from group membership
    // selected in the "field_group_affiliation" profile field.
    if (
      $this->hasField('field_group_affiliation') &&
      !$this->get('field_group_affiliation')->isEmpty()
    ) {
      $group = $this->get('field_group_affiliation')->entity;
      assert($group instanceof GroupInterface);
      $value['affiliation_name'] = $group->label();

      // Additionally, add the group type id to the affiliation.
      $value['affiliation_type'] = $group->getGroupType()->label();

      $account = $this->getOwner();
      $membership = GroupMembership::loadSingle($group, $account);
      if (
        $membership instanceof GroupMembership &&
        $membership->hasField('field_affiliation_function')
      ) {
        $value['affiliation_function'] = $membership->get('field_affiliation_function')->getString();
      }
    }

    // 2. Get an affiliation function from the "field_other_affiliations" field.
    if (empty($value)) {
      if (
        $this->hasField('field_other_affiliations') &&
        !$this->get('field_other_affiliations')->isEmpty()
      ) {
        $paragraph = $this->get('field_other_affiliations')->entity;
        if ($paragraph instanceof ParagraphInterface) {
          $value = [
            'affiliation_name' => $paragraph->get('field_affiliation_org_name')->getString(),
            'affiliation_function' => $paragraph->get('field_affiliation_org_function')->getString(),
            'affiliation_type' => 'non-platform affiliation',
          ];
        }
      }
    }

    $affiliation = $value ?? [];
    return $affiliation;
  }

  /**
   * {@inheritDoc}
   */
  public function getPrimaryAffiliationName(): string|MarkupInterface {
    return $this->getPrimaryAffiliation()['affiliation_name'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function getPrimaryAffiliationFunction(): string|MarkupInterface {
    return $this->getPrimaryAffiliation()['affiliation_function'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function getPrimaryAffiliationType(): string|MarkupInterface {
    return $this->getPrimaryAffiliation()['affiliation_type'] ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function addNonPlatformAffiliation(?string $affiliation_name = NULL, ?string $affiliation_function = NULL): static {
    if (empty($affiliation_name) && empty($affiliation_function)) {
      return $this;
    }

    // Check if the affiliation doesn't exist.
    // Make sure we don't create a paragraph with the same values (duplicate).
    if (!$this->get('field_other_affiliations')->isEmpty()) {
      $paragraph_ids = array_column($this->get('field_other_affiliations')->getValue(), 'target_id');
      $paragraphs_query = \Drupal::entityQuery('paragraph')
        ->accessCheck(FALSE)
        ->condition('type', 'other_affiliations')
        ->condition('id', $paragraph_ids, 'IN');

      if ($affiliation_name) {
        $paragraphs_query->condition('field_affiliation_org_name', $affiliation_name);
      }
      else {
        $paragraphs_query->notExists('field_affiliation_org_name');
      }

      if ($affiliation_function) {
        $paragraphs_query->condition('field_affiliation_org_function', $affiliation_function);
      }
      else {
        $paragraphs_query->notExists('field_affiliation_org_function');
      }

      $exists = $paragraphs_query->execute();
      if ($exists) {
        return $this;
      }
    }

    $paragraph = Paragraph::create([
      'type' => 'other_affiliations',
      'field_affiliation_org_name' => $affiliation_name,
      'field_affiliation_org_function' => $affiliation_function,
    ]);
    $paragraph->save();

    // Append the paragraph reference to the profile field.
    $this->get('field_other_affiliations')->appendItem([
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ]);

    return $this;
  }

}
