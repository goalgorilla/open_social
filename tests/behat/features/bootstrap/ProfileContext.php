<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;

/**
 * Defines test steps around user profiles and profile management.
 */
class ProfileContext extends RawMinkContext {

  use EntityTrait;

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here, so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Go to the profile page for the current user.
   *
   * @When I am viewing my profile
   */
  public function amViewingMyProfile() : void {
    $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    $this->visitPath("/user/$user_id");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Try to view a specific profile even if you might not have access.
   *
   * @When I try to view the profile of :user
   */
  public function attemptViewingProfile(string $user) : void {
    if ($user === 'anonymous') {
      $user_ids = [0];
    }
    else {
      $user_ids = \Drupal::entityQuery('user')
        ->accessCheck(FALSE)
        ->condition('name', $user)
        ->execute();

      if (count($user_ids) !== 1) {
        throw new \InvalidArgumentException("Could not find user with username `$user'.");
      }
    }

    $user_id = reset($user_ids);
    $this->visitPath("/user/$user_id");
  }

  /**
   * Create or update the profile for a user with a specific nickname.
   *
   * Updates a profile in the form:
   * | field_profile_first_name | John |
   * | field_profile_last_name  | Doe  |
   *
   * @Given user :username has a profile filled with:
   */
  public function userHasProfile(string $username, TableNode $profileTable) : void {
    $profile = $profileTable->getRowsHash();
    $profile['owner'] = $username;
    $this->profileUpdate($profile);
  }

  /**
   * Create or update the profile for the current user.
   *
   * Updates a profile in the form:
   * | field_profile_first_name | John |
   * | field_profile_last_name  | Doe  |
   *
   * @Given I have a profile filled with:
   * @Given have a profile filled with:
   */
  public function iHaveProfile(TableNode $profileTable) : void {
    $profile = $profileTable->getRowsHash();
    if (isset($profile['uid'])) {
      throw new \InvalidArgumentException("Should not set `uid` for profile, use 'user :username has a profile filled with' instead.");
    }
    if (isset($profile['owner'])) {
      throw new \InvalidArgumentException("Should not set `owner` for profile, use 'user :username has a profile filled with' instead.");
    }
    $this->profileUpdate($profile);
  }

  /**
   * Go to the profile edit page for the current user.
   *
   * @When I am editing my profile
   */
  public function amEditingMyProfile() : void {
    $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    $this->visitPath("/user/$user_id/profile");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Go to the profile edit page for the specified user.
   *
   * @When I try to edit the profile of :user
   * @When I try to edit my profile
   */
  public function amEditingProfileOf(?string $user = NULL) : void {
    if ($user === NULL) {
      $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    }
    elseif ($user === 'anonymous') {
      $user_id = 0;
    }
    else {
      $user_ids = \Drupal::entityQuery('user')
        ->accessCheck(FALSE)
        ->condition('name', $user)
        ->execute();

      if (count($user_ids) !== 1) {
        throw new \InvalidArgumentException("Could not find user with username `$user'.");
      }

      $user_id = reset($user_ids);
    }

    $this->visitPath("/user/$user_id/profile");
  }

  /**
   * Manage whether unique nicknames are enforced.
   *
   * @param string $state
   *   Either enabled or disabled.
   *
   * @Given unique nicknames for users is :state
   */
  public function setUniqueNicknames(string $state) : void {
    assert($state === "enabled" || $state === "disabled", ":state must be one of 'enabled' or 'disabled' (got '$state')");

    \Drupal::configFactory()->getEditable("social_profile_fields.settings")->set("nickname_unique_validation", $state === "enabled")->save();
    \Drupal::service("entity_field.manager")->clearCachedFieldDefinitions();
  }

  /**
   * Check the state of unique nicknames enforcement.
   *
   * @param string $state
   *   Either enabled or disabled.
   *
   * @Then unique nicknames for users should be :state
   */
  public function assertUniqueNicknames(string $state) : void {
    assert($state === "enabled" || $state === "disabled", ":state must be one of 'enabled' or 'disabled' (got '$state')");
    // For some reason the way Drupal Extension runs Drupal the permissions get
    // cached in our runtime, so we need to cache bust to ensure we can actually
    // see the result of the form save.
    \Drupal::configFactory()->clearStaticCache();

    $expectedState = $state === "enabled";
    $actualState = \Drupal::config("social_profile_fields.settings")->get("nickname_unique_validation");
    if ($expectedState !== $actualState) {
      throw new \RuntimeException("Expected unique nicknames to be $state but got '" . ($actualState ? "enabled" : "disabled") . "'");
    }
  }

  /**
   * Update a profile for a user.
   *
   * @param array $profile
   *   The field values for the profile.
   *
   * @return \Drupal\profile\Entity\Profile
   *   The updated profile.
   */
  private function profileUpdate(array $profile) : Profile {
    if (!isset($profile['owner'])) {
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $profile['uid'] ??= is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($profile['owner']);
      if ($account->id() !== 0) {
        $profile['uid'] ??= $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $profile['owner']));
      }
      unset($profile['owner']);
    }

    if ($profile['uid'] === 0) {
      throw new \InvalidArgumentException("Can not update the profile of the anonymous user");
    }

    $profile['type'] = 'profile';
    $this->validateEntityFields("profile", $profile);
    $profile_object = \Drupal::entityTypeManager()->getStorage('profile')->loadByUser(User::load($profile['uid']), 'profile');
    if ($profile_object instanceof ProfileInterface) {
      foreach ($profile as $field => $value) {
        $profile_object->set($field, $value);
      }
    }
    else {
      $profile_object = Profile::create($profile);
    }

    $violations = $profile_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The profile you tried to update is invalid: $violations");
    }
    $profile_object->save();

    return $profile_object;
  }

}
