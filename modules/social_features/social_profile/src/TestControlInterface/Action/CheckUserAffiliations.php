<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch;
use Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints asserting a user's profile affiliation state.
 *
 * These never touch a browser — they compare CMS state (which groups a
 * profile is affiliated with, and how) against an expected list — so they
 * qualify as Test Control Interface operations even though the Behat steps
 * they replace read like assertions rather than setup.
 *
 * The four operations that take an expected-labels list are declared
 * OperationType::Write, not Read, even though they don't mutate anything:
 * the framework rejects a $body parameter on Read operations (Read maps to
 * GET, which carries no body), so Write is the only way to accept the
 * table body these steps need. The three no-argument "has no X" checks
 * stay Read, since they don't have this problem.
 */
final class CheckUserAffiliations {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Asserts a user has exactly the given affiliations, in order.
   *
   * @param string $username
   *   The username whose affiliations are being checked.
   * @param string $profileType
   *   The profile type (bundle) to check.
   * @param list<string> $body
   *   Expected group labels, in order.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_all_check',
    patterns: [':username has affiliations on profile type :profileType:'],
    type: OperationType::Write,
  )]
  public function checkAllAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
    #[Example(['Group A', 'Group B'])]
    array $body,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getAllUserAffiliationGroupIds', $body);
  }

  /**
   * Asserts a user has exactly the given user-owned affiliations, in order.
   *
   * @param string $username
   *   The username whose affiliations are being checked.
   * @param string $profileType
   *   The profile type (bundle) to check.
   * @param list<string> $body
   *   Expected group labels, in order.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_owned_check',
    patterns: [':username has these exact user-owned affiliations on profile type :profileType:'],
    type: OperationType::Write,
  )]
  public function checkUserOwnedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
    #[Example(['Group A', 'Group C'])]
    array $body,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getUserOwnedAffiliationGroupIds', $body);
  }

  /**
   * Asserts a user has exactly the given system-added affiliations, in order.
   *
   * @param string $username
   *   The username whose affiliations are being checked.
   * @param string $profileType
   *   The profile type (bundle) to check.
   * @param list<string> $body
   *   Expected group labels, in order.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_system_added_check',
    patterns: [':username has these exact system-added affiliations on profile type :profileType:'],
    type: OperationType::Write,
  )]
  public function checkSystemAddedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
    #[Example(['Group B', 'Group D'])]
    array $body,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getSystemAddedAffiliationGroupIds', $body);
  }

  /**
   * Asserts a user has exactly the given user-removed affiliations, in order.
   *
   * @param string $username
   *   The username whose affiliations are being checked.
   * @param string $profileType
   *   The profile type (bundle) to check.
   * @param list<string> $body
   *   Expected group labels, in order.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_removed_check',
    patterns: [':username has these exact user-removed affiliations on profile type :profileType:'],
    type: OperationType::Write,
  )]
  public function checkUserRemovedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
    #[Example(['Group X'])]
    array $body,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getUserRemovedAffiliationGroupIds', $body);
  }

  /**
   * Asserts a user has no user-owned affiliations.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_owned_check_empty',
    patterns: [':username has no user-owned affiliations on profile type :profileType'],
    type: OperationType::Read,
  )]
  public function checkNoUserOwnedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getUserOwnedAffiliationGroupIds', []);
  }

  /**
   * Asserts a user has no system-added affiliations.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_system_added_check_empty',
    patterns: [':username has no system-added affiliations on profile type :profileType'],
    type: OperationType::Read,
  )]
  public function checkNoSystemAddedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getSystemAddedAffiliationGroupIds', []);
  }

  /**
   * Asserts a user has no user-removed affiliations.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationStateMatchedSuccess|\Drupal\social_profile\TestControlInterface\Error\AffiliationStateMismatch
   *   The operation result.
   */
  #[Operation(
    id: 'user_affiliations_removed_check_empty',
    patterns: [':username has no user-removed affiliations on profile type :profileType'],
    type: OperationType::Read,
  )]
  public function checkNoUserRemovedAffiliations(
    #[Example('Alice')]
    string $username,
    #[Example('profile')]
    string $profileType,
  ): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    return $this->assertAffiliations($username, $profileType, 'getUserRemovedAffiliationGroupIds', []);
  }

  /**
   * Compares a profile's affiliation state (by accessor method) to a list.
   *
   * @param string $username
   *   The username whose affiliations are being checked.
   * @param string $profileType
   *   The profile type (bundle) to check.
   * @param string $affiliationStateMethod
   *   The profile accessor method to call, e.g.
   *   getUserOwnedAffiliationGroupIds.
   * @param list<string> $expectedLabels
   *   Expected group labels, in order.
   */
  private function assertAffiliations(string $username, string $profileType, string $affiliationStateMethod, array $expectedLabels): AffiliationStateMatchedSuccess|AffiliationStateMismatch {
    $user = $this->loadUserByName($username);
    $profile = $this->loadUserProfileByType($user, $profileType);

    if (!method_exists($profile, $affiliationStateMethod)) {
      throw new BadRequestHttpException(sprintf("Method '%s' not found on profile.", $affiliationStateMethod));
    }

    $groupStorage = $this->entityTypeManager->getStorage('group');

    $normalizedExpected = [];
    foreach ($expectedLabels as $label) {
      $groups = $groupStorage->loadByProperties(['label' => $label]);
      $group = reset($groups);
      if ($group === FALSE) {
        throw new BadRequestHttpException(sprintf("Group '%s' not found.", $label));
      }
      $normalizedExpected[] = (string) $group->label();
    }

    $actualIds = $profile->$affiliationStateMethod();
    $actualLabels = [];
    foreach ($actualIds as $groupId) {
      $group = $groupStorage->load($groupId);
      if ($group !== NULL) {
        $actualLabels[] = (string) $group->label();
      }
    }

    if ($normalizedExpected !== $actualLabels) {
      return new AffiliationStateMismatch(expected: $normalizedExpected, actual: $actualLabels);
    }

    return new AffiliationStateMatchedSuccess(groupLabels: $actualLabels);
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

}
