<?php

namespace Drupal\social_profile\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_profile\AutomaticGroupAffiliation;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\social_profile\Event\SocialProfilePrePresaveSubmitEvent;

/**
 * Event subscriber for handling social profile pre-presave form submissions.
 *
 * This subscriber listens to the event triggered before a social profile entity
 * is saved, specifically to detect changes in group affiliations and their
 * membership fields.
 *
 * It compares the current profile's group affiliation values with the original
 * saved values, including the order of affiliations, and marks the profile if
 * any changes are detected.
 *
 * Additionally, it detects changes made to inline entity forms (IEF) within
 * group membership affiliations, such as updates to membership-specific fields
 * (e.g., function, role).
 *
 * The marked change state influences subsequent processing in systems such as
 * AutomaticGroupAffiliation to apply appropriate logic depending on whether the
 * user modified group affiliations or membership details.
 *
 * @see AutomaticGroupAffiliation
 *   To learn more about automatic affiliations.
 */
class SocialProfilePrePresaveFormSubmitSubscriber implements EventSubscriberInterface {

  /**
   * Subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   The group affiliation.
   * @param \Drupal\social_profile\AutomaticGroupAffiliation $automaticGroupAffiliation
   *   The automatic group affiliation.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GroupAffiliation $groupAffiliation,
    protected AutomaticGroupAffiliation $automaticGroupAffiliation,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SocialProfilePrePresaveSubmitEvent::EVENT_NAME => 'onSocialProfilePrePresaveFormSubmit',
    ];
  }

  /**
   * Handles the pre-presave submission of the social profile form.
   *
   * This method checks whether the user has made any changes to the group
   * affiliation field on their profile, either by modifying the selected
   * group affiliations or by editing inline group membership fields.
   * If any change is detected, the profile is marked accordingly.
   *
   * This mark is used to determine correct behaviour based on this
   * at AutomaticGroupAffiliation
   *
   * @param \Drupal\social_profile\Event\SocialProfilePrePresaveSubmitEvent $event
   *   The event triggered before the profile entity is saved.
   *
   * @return void
   *   Return vodi.
   */
  public function onSocialProfilePrePresaveFormSubmit(SocialProfilePrePresaveSubmitEvent $event): void {
    /** @var \Drupal\social_profile\Entity\ProfileAffiliationInterface $profile */
    $profile = $event->getProfile();

    // Return early if the profile does not have the affiliation field.
    if (!$profile->hasField(GroupAffiliation::AFFILIATION_FIELD_NAME)) {

      return;
    }

    // Return early if the affiliation feature is disabled or the current group
    // type(s) configuration does not support group affiliations.
    if (
      !$this->groupAffiliation->isAffiliationFeatureEnabled() ||
      !$this->groupAffiliation->isGroupAffiliationEnabled()
    ) {

      return;
    }

    // Note: Whether affiliation entities (groups) were added/removed/reordered,
    // on user profile is validated on updateUserAffiliationsByUser, because at
    // this point, profile form is not yet processed correctly, while values
    // from inline entity forms are.
    //
    // Check if any of the inline group membership fields (e.g. function/role)
    // were modified by the user. If so, mark as changed.
    // This applies only to group types that include such fields.
    if ($this->hasUserUpdatedAffiliatedGroupMemberships($event)) {
      $profile->markAffiliationsChangedByUser();
    }
  }

  /**
   * Determines if the user has updated any affiliated group memberships.
   *
   * This checks whether the submitted values in the group membership form
   * differ from the original default values. If at least one difference is
   * found, it returns TRUE.
   *
   * @param \Drupal\social_profile\Event\SocialProfilePrePresaveSubmitEvent $event
   *   The event triggered before the social profile is presaved.
   *
   * @return bool
   *   TRUE if the user has made any changes to group membership fields,
   *   FALSE otherwise.
   */
  private function hasUserUpdatedAffiliatedGroupMemberships(SocialProfilePrePresaveSubmitEvent $event): bool {
    $form_state = $event->getFormState();
    $form = $event->getForm();

    // Iterate over all group affiliation widgets submitted in the form.
    foreach ($form_state->getValue(GroupAffiliation::AFFILIATION_FIELD_NAME) as $key => $group_affiliation) {

      // Skip non-array keys such as objects.
      if (!is_array($group_affiliation)) {
        continue;
      }

      // Skip if the membership form subcomponent is empty (no affiliation
      // fields).
      if (!empty($group_affiliation['container']['group_membership_form'])) {

        // Retrieve the submitted (updated) membership values.
        $updated_membership = $form[GroupAffiliation::AFFILIATION_FIELD_NAME]['widget'][$key]['container']['group_membership_form']['#default_value'];
        $updated_membership_values = $this->automaticGroupAffiliation->getDefaultMembershipAffiliationValues($updated_membership);

        // Retrieve the original default values from the form structure.
        // See GroupAffiliationWidget to learn more about
        // #group_membership_default_values.
        $original_membership_values = $form[GroupAffiliation::AFFILIATION_FIELD_NAME]['widget'][$key]['container']['group_membership_form']['#group_membership_default_values'];

        // If the values differ, mark that the user updated group membership.
        if ($updated_membership_values !== $original_membership_values) {
          return TRUE;
        }
      }
    }

    // No differences found — user did not update any group membership fields.
    return FALSE;
  }

}
