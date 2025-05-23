<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\AutomaticGroupAffiliation;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Automatic group affiliation hooks.
 *
 * @internal
 */
class AutomaticGroupAffiliationHooks implements ContainerInjectionInterface {

  /**
   * GroupAffiliationGroupTypeHooks constructor.
   *
   * @param \Drupal\social_profile\AutomaticGroupAffiliation $automaticGroupAffiliation
   *   Automatic group affiliation service.
   */
  public function __construct(
    protected AutomaticGroupAffiliation $automaticGroupAffiliation,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_profile.automatic_group_affiliations'),
    );
  }

  /**
   * Updates automatic affiliations when the user updates their profile.
   *
   * Triggered on profile presave. Ensures the automatic affiliation logic is
   * applied whenever a user updates their profile by checking for changes and
   * adjusting affiliation data ("affiliation owned count" and "user-removed")
   * accordingly.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The user profile entity being saved.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   To learn more about automatic affiliations.
   */
  #[Hook('profile_presave')]
  public function updateAutomaticAffiliationsOnUserProfileUpdate(ProfileInterface $user_profile): void {
    if ($user_profile instanceof ProfileAffiliationInterface) {
      $this->automaticGroupAffiliation->updateUserAffiliationsByUser($user_profile);
    }
  }

  /**
   * Updates automatic affiliations when a new group membership is created.
   *
   * Triggered on group content insert. Ensures the automatic affiliation logic
   * is applied when a user joins a group by updating system-added affiliations
   * and recalculating their positions as needed.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The group relationship entity being inserted.
   *
   * @return void
   *   Return void.
   *
   * @see AutomaticGroupAffiliation
   *   To learn more about automatic affiliations.
   */
  #[Hook('group_content_insert')]
  public function updateAutomaticAffiliationsOnMembershipCreate(GroupRelationshipInterface $group_relationship): void {
    if ($group_relationship instanceof GroupMembership) {
      $this->automaticGroupAffiliation->userMembershipIsCreated($group_relationship);
    }
  }

  /**
   * Updates automatic affiliations when a group membership is updated.
   *
   * Triggered on group content update. This method ensures that automatic
   * affiliation logic is applied whenever a user's membership changes,
   * updating system-added affiliations and recalculating their order as needed.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The group relationship entity being updated.
   *
   * @return void
   *   Returns void.
   *
   * @see AutomaticGroupAffiliation
   *   To learn more about automatic affiliations.
   */
  #[Hook('group_content_update')]
  public function updateAutomaticAffiliationsOnMembershipUpdate(GroupRelationshipInterface $group_relationship): void {
    if ($group_relationship instanceof GroupMembership) {
      $this->automaticGroupAffiliation->userMembershipIsUpdated($group_relationship);
    }
  }

  /**
   * Removes affiliation when a group membership is deleted.
   *
   * Triggered on group content deletion. If the deleted membership was part of
   * the affiliation list (regardless of whether it was user-owned or
   * system-added), this method removes it from the user's affiliation field and
   * from the list of user-removed affiliations.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The group relationship entity being deleted.
   *
   * @return void
   *   Returns void.
   *
   * @see AutomaticGroupAffiliation
   *   To learn more about automatic affiliations.
   */
  #[Hook('group_content_delete')]
  public function updateAutomaticAffiliationsOnMembershipDelete(GroupRelationshipInterface $group_relationship): void {
    if ($group_relationship instanceof GroupMembership) {
      $this->automaticGroupAffiliation->userMembershipIsDeleted($group_relationship);
    }
  }

}
