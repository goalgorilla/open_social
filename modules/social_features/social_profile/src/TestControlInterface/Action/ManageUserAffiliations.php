<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_profile\AutomaticGroupAffiliation;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile\TestControlInterface\Success\UserAffiliationUpdatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints mutating a user's profile affiliation state.
 */
final class ManageUserAffiliations {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Marks the first N affiliations on a user's profile as user-owned.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\UserAffiliationUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_owned_count_set',
    patterns: ['I define first :number affiliations as user-owned for :username on profile type :profileType'],
    type: OperationType::Write,
  )]
  public function defineFirstAffiliationsAsUserOwned(
    #[Example('3')]
    string $number,
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
  ): UserAffiliationUpdatedSuccess {
    if (!ctype_digit($number)) {
      throw new BadRequestHttpException('Affiliation count must be a non-negative integer.');
    }
    $count = (int) $number;

    $user = $this->loadUserByName($username);
    $profile = $this->loadUserProfileByType($user, $profileType);
    if (!$profile->hasField(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME)) {
      throw new BadRequestHttpException(sprintf("Profile of type '%s' does not have the affiliation-owned-count field.", $profileType));
    }

    $totalAffiliations = count($profile->getAllUserAffiliationGroupIds());
    if ($count > $totalAffiliations) {
      throw new BadRequestHttpException(sprintf('Affiliation count %d exceeds the total number of affiliations (%d) on this profile.', $count, $totalAffiliations));
    }

    $profile->set(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME, $count);
    $profile->save();

    return new UserAffiliationUpdatedSuccess(profileId: (int) $profile->id());
  }

  /**
   * Inserts a group into a user's user-owned affiliations at a position.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\UserAffiliationUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliation_add_at_position',
    patterns: ['I add :groupName on position :position to user-owned affiliation for user :username on profile type :profileType'],
    type: OperationType::Write,
  )]
  public function addGroupToUserAffiliationProfile(
    #[Example('Group A')]
    string $groupName,
    #[Example('2')]
    string $position,
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
  ): UserAffiliationUpdatedSuccess {
    $user = $this->loadUserByName($username);
    $profile = $this->loadUserProfileByType($user, $profileType);
    $group = $this->loadGroupByLabel($groupName);

    if (!$profile->hasField(GroupAffiliation::AFFILIATION_FIELD_NAME)) {
      throw new BadRequestHttpException(sprintf("Profile of type '%s' does not have the %s field.", $profileType, GroupAffiliation::AFFILIATION_FIELD_NAME));
    }
    if (!$profile->hasField(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME)) {
      throw new BadRequestHttpException(sprintf("Profile of type '%s' does not have the affiliation-owned-count field.", $profileType));
    }

    if (!ctype_digit($position) || (int) $position < 1) {
      throw new BadRequestHttpException('Position must be 1 or greater.');
    }
    $positionInt = (int) $position;

    $currentCount = max(0, $profile->getUserOwnedAffiliationsCount());
    if ($positionInt > $currentCount + 1) {
      throw new BadRequestHttpException(sprintf('Position %d is invalid. Current affiliation count is %d. You may only add to position %d or lower.', $positionInt, $currentCount, $currentCount + 1));
    }

    $affiliations = $profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->getValue();
    array_splice($affiliations, $positionInt - 1, 0, [['target_id' => $group->id()]]);
    $affiliations = array_values($affiliations);

    $profile->set(GroupAffiliation::AFFILIATION_FIELD_NAME, $affiliations);
    $profile->set(AutomaticGroupAffiliation::AFFILIATION_OWNED_COUNT_FILED_NAME, $currentCount + 1);
    $profile->save();

    return new UserAffiliationUpdatedSuccess(profileId: (int) $profile->id());
  }

  /**
   * Adds a non-platform (off-platform organization) affiliation.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\UserAffiliationUpdatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliation_non_platform_add',
    patterns: ['The user :username has non-platform affiliation :affiliationName with function :affiliationFunction'],
    type: OperationType::Write,
  )]
  public function addNonPlatformAffiliation(
    #[Example('Alice')]
    string $username,
    #[Example('Organization Name')]
    string $affiliationName,
    #[Example('Job Title')]
    string $affiliationFunction,
  ): UserAffiliationUpdatedSuccess {
    $user = $this->loadUserByName($username);
    $profile = $this->loadUserProfileByType($user, 'profile');

    $profile->set('field_enable_other_affiliations', TRUE);
    $profile->addNonPlatformAffiliation($affiliationName, $affiliationFunction);
    $profile->save();

    return new UserAffiliationUpdatedSuccess(profileId: (int) $profile->id());
  }

  /**
   * Loads a user entity by username.
   */
  private function loadUserByName(string $username): UserInterface {
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);
    $user = reset($users);
    if (!$user instanceof UserInterface) {
      throw new BadRequestHttpException(sprintf("User '%s' not found.", $username));
    }

    return $user;
  }

  /**
   * Loads a profile entity of a specific type for a given user.
   */
  private function loadUserProfileByType(UserInterface $user, string $profileType): ProfileAffiliationInterface {
    $profiles = $this->entityTypeManager->getStorage('profile')->loadByProperties([
      'uid' => $user->id(),
      'type' => $profileType,
    ]);
    $profile = reset($profiles);
    if (!$profile instanceof ProfileAffiliationInterface) {
      throw new BadRequestHttpException(sprintf("Profile of type '%s' not found for user '%s'.", $profileType, $user->getAccountName()));
    }

    return $profile;
  }

  /**
   * Loads a group entity by its label.
   */
  private function loadGroupByLabel(string $label): GroupInterface {
    $groups = $this->entityTypeManager->getStorage('group')->loadByProperties(['label' => $label]);
    $group = reset($groups);
    if (!$group instanceof GroupInterface) {
      throw new BadRequestHttpException(sprintf("Group '%s' does not exist.", $label));
    }

    return $group;
  }

}
