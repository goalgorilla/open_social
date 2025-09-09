<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\user_segments\Entity\UserSegment;
use Drupal\social\Behat\EntityTrait;
use Drupal\social\Behat\UserSegmentTrait;
use Drupal\social\Behat\SocialMinkContext;
use Drupal\social\Behat\SocialDrupalContext;

/**
 * Defines test steps around the usage of user segments.
 */
class UserSegmentContext extends RawMinkContext {

  use EntityTrait;
  use UserSegmentTrait;

  private const CREATE_PAGE = "/admin/people/segment/add";

  /**
   * Keep track of all user segments that are created, so they can easily be removed.
   */
  private array $userSegments = [];

  /**
   * Keep track of the last created user segment so that it can be validated.
   */
  private ?array $lastCreatedValues = NULL;

  /**
   * The Drupal mink context is useful for validation of content.
   */
  private MinkContext $minkContext;

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void
  {
    $environment = $scope->getEnvironment();

    $this->minkContext = $environment->getContext(SocialMinkContext::class);
    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * View the user segment creation page.
   *
   * @When /^(?:|I )view the user segment creation page$/
   */
  public function whenIViewTheUserSegmentCreationPage() : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Create multiple user segments at the start of a test.
   *
   * Creates user segments provided in the form:
   * | author | label        | description     | status | visibility_option | rules         |
   * | user-1 | My Segment   | My description  | 1      | 1                 | {"rules": []} |
   * | user-1 | My Segment 2 | My description  | 1      | 0                 | {"rules": []} |
   * | ...    | ...          | ...             | ...    | ...               | ...           |
   *
   * @Given user segments:
   */
  public function createUserSegments(TableNode $userSegmentsTable) {
    foreach ($userSegmentsTable->getHash() as $userSegmentHash) {
      $userSegment = $this->userSegmentCreate($userSegmentHash);
      $this->userSegments[$userSegment->id()] = $userSegment;
    }
  }

  /**
   * Create multiple user segments at the start of a test.
   *
   * Creates user segments provided in the form:
   * | label      | description     | status | visibility_option | rules |
   * | My Segment | My description  | 1      | 1                 | {"rules": []} |
   * | ...        | ...             | ...    | ...               | ...   |
   *
   * @Given user segments with non-anonymous owner:
   */
  public function createUserSegmentsWithOwner(TableNode $userSegmentsTable) {
    // Create a new random user to own our user segments, this ensures the author
    // isn't anonymous.
    $user = (object) [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'role' => "authenticated",
    ];
    $user->mail = "{$user->name}@example.com";

    $this->drupalContext->userCreate($user);

    foreach ($userSegmentsTable->getHash() as $userSegmentHash) {
      if (isset($userSegmentHash['author'])) {
        throw new \RuntimeException("Can not specify an author when using the 'user segments with non-anonymous owner:' step, use 'user segments:' instead.");
      }

      $userSegmentHash['author'] = $user->name;

      $userSegment = $this->userSegmentCreate($userSegmentHash);
      $this->userSegments[$userSegment->id()] = $userSegment;
    }
  }

  /**
   * Create multiple user segments at the start of a test.
   *
   * Creates user segments provided in the form:
   * | label      | description     | status | visibility_option | rules |
   * | My Segment | My description  | 1      | 1                 | {"rules": []} |
   * | ...        | ...             | ...    | ...               | ...   |
   *
   * @Given user segments owned by current user:
   */
  public function createUserSegmentsOwnedByCurrentUser(TableNode $userSegmentsTable) {
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
    foreach ($userSegmentsTable->getHash() as $userSegmentHash) {
      if (isset($userSegmentHash['author'])) {
        throw new \RuntimeException("Can not specify an author when using the 'user segments owned by current user:' step, use 'user segments:' instead.");
      }

      // We specify the owner for each user segment to be the current user.
      // `userSegmentCreate` will load the user by name so we fall back to 'anonymous'
      // if the current user isn't logged in.
      $userSegmentHash['author'] = (is_object($current_user) ? $current_user->name : NULL) ?? 'anonymous';

      $userSegment = $this->userSegmentCreate($userSegmentHash);
      $this->userSegments[$userSegment->id()] = $userSegment;
    }
  }

  /**
   * Fill out the user segment creation form and submit.
   *
   * Example: When I create a user segment using its creation page:
   *              | Label       | My Test Segment  |
   *              | Description | It's for testing |
   *              | Status      | 1                |
   *
   * @When I create a user segment using its creation page:
   * @When create a user segment using its creation page:
   */
  public function whenICreateAUserSegmentUsingTheForm(TableNode $fields): void {
    $this->visitPath(self::CREATE_PAGE);
    if ($this->getSession()->getStatusCode() !== 200) {
      throw new \RuntimeException("Could not go to `" . self::CREATE_PAGE . "` page.");
    }

    $page = $this->getSession()->getPage();

    $userSegment = [];
    foreach ($fields->getRowsHash() as $field => $value) {
      $key = strtolower($field);
      $userSegment[$key] = $value;

      // Handle different field types
      if ($key === "label") {
        $page->fillField("Label", $value);
      }
      elseif ($key === "description") {
        $page->fillField("Description", $value);
      }
      elseif ($key === "status") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $page->checkField("Status");
        } else {
          $page->uncheckField("Status");
        }
      }
      elseif ($key === "visibility_option") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $page->checkField("Use this segment as a visibility option");
        } else {
          $page->uncheckField("Use this segment as a visibility option");
        }
      }
      elseif ($key === "visibility_option_label") {
        $page->fillField("Visibility option label", $value);
      }
      elseif ($key === "rules") {
        // For rules, we expect a JSON string.
        $page->fillField("Rules", $value);
      }
      else {
        $page->fillField($field, $value);
      }
    }

    // Submit the page.
    $page->pressButton("Save");

    // Keep track of the user segment we just created so that we can delete it after
    // the test but also so that we can validate what things are in there.
    $userSegmentId = $this->getNewestUserSegmentIdFromLabel($userSegment['label']);
    if ($userSegmentId === NULL) {
      throw new \RuntimeException("Could not find created user segment by label, perhaps creation failed or there are multiple user segments with the same label.");
    }

    $this->lastCreatedValues = $userSegment;

    $createdUserSegment = UserSegment::load($userSegmentId);
    assert($createdUserSegment instanceof UserSegment);
    $this->userSegments[$userSegmentId] = $createdUserSegment;
  }

  /**
   * Check that a user segment that was just created is properly shown.
   *
   * @Then I should see the user segment I just created
   * @Then should see the user segment I just created
   */
  public function thenIShouldSeeTheUserSegmentIJustCreated() : void {
    $this->minkContext->assertPageContainsText("User segment {$this->lastCreatedValues['label']} has been created.");

    foreach ($this->lastCreatedValues as $field => $value) {
      if ($field === "label") {
        $this->minkContext->assertPageContainsText($value);
      }
      elseif ($field === "description") {
        $this->minkContext->assertPageContainsText($value);
      }
      elseif ($field === "status") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $this->minkContext->assertPageContainsText("Enabled");
        } else {
          $this->minkContext->assertPageContainsText("Disabled");
        }
      }
      elseif ($field === "visibility_option") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $this->minkContext->assertPageContainsText("Yes");
        } else {
          $this->minkContext->assertPageContainsText("No");
        }
      }
      elseif ($field === "visibility_option_label") {
        $this->minkContext->assertPageContainsText($value);
      }
      else {
        $this->minkContext->assertPageContainsText($value);
      }
    }
  }

  /**
   * View the user segment overview.
   *
   * @When I am viewing the user segments overview
   * @When am viewing the user segments overview
   */
  public function viewUserSegmentOverview() : void {
    $this->visitPath("/admin/people/segments");
  }

  /**
   * Open the user segment on its default page.
   *
   * @When I am viewing the user segment :userSegment
   * @When am viewing the user segment :userSegment
   */
  public function viewingUserSegment(string $userSegment) : void {
    $userSegmentId = $this->getNewestUserSegmentIdFromLabel($userSegment);
    if ($userSegmentId === NULL) {
      throw new \RuntimeException("User segment '$userSegment' does not exist.");
    }
    $this->visitPath("/admin/people/segment/$userSegmentId");
  }

  /**
   * Edit a specific user segment.
   *
   * @When I am editing the user segment :userSegment
   * @When am editing the user segment :userSegment
   */
  public function editingUserSegment(string $userSegment) : void {
    $this->viewPageInUserSegment("edit", $userSegment);
  }

  /**
   * Open the user segment on a specific page.
   *
   * @When I am viewing the :userSegmentPage page of user segment :userSegment
   * @When am viewing the :userSegmentPage page of user segment :userSegment
   */
  public function viewPageInUserSegment(string $userSegmentPage, string $userSegment) : void {
    $userSegmentId = $this->getNewestUserSegmentIdFromLabel($userSegment);
    if ($userSegmentId === NULL) {
      throw new \RuntimeException("User segment '$userSegment' does not exist.");
    }
    $this->visitPath("/admin/people/segment/$userSegmentId/$userSegmentPage");
  }

  /**
   * Assert we're on the user segment page.
   *
   * Can be used to check that a redirect was implemented correctly.
   *
   * @Then I should be viewing the user segment :userSegment
   * @Then should be viewing the user segment :userSegment
   */
  public function shouldBeViewingUserSegment(string $userSegment) : void {
    $userSegmentId = $this->getNewestUserSegmentIdFromLabel($userSegment);
    if ($userSegmentId === NULL) {
      throw new \RuntimeException("User segment '$userSegment' does not exist.");
    }
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals("/admin/people/segment/$userSegmentId");
  }

  /**
   * Create a user segment.
   *
   * @param array $userSegment
   *   The values to pass to UserSegment::create. `author` can be set to a username
   *   which will be converted to a uid.
   *
   * @return \Drupal\user_segments\Entity\UserSegment
   *   The created user segment.
   */
  private function userSegmentCreate(array $userSegment) {
    if (!isset($userSegment['author'])) {
      throw new \RuntimeException("You must specify an `author` when creating a user segment. Specify the `author` field if using `@Given user segments:` or use one of `@Given user segments with non-anonymous owner:` or `@Given user segments owned by current user:` instead.");
    }

    $account = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $userSegment['author']]);
    $account = !empty($account) ? reset($account) : FALSE;
    if ($account === FALSE) {
      throw new \RuntimeException(sprintf("User with username '%s' does not exist.", $userSegment['author']));
    }
    $userSegment['uid'] = $account->id();
    unset($userSegment['author']);

    // Convert rules to JSON string if it's an array
    if (isset($userSegment['rules']) && is_array($userSegment['rules'])) {
      $userSegment['rules'] = json_encode($userSegment['rules']);
    }

    // Let's create some user segments.
    $this->validateEntityFields('user_segment', $userSegment);
    $userSegmentObject = UserSegment::create($userSegment);
    $violations = $userSegmentObject->validate();
    if ($violations->count() !== 0) {
      throw new \RuntimeException("The user segment you tried to create is invalid: $violations");
    }
    $userSegmentObject->save();

    return $userSegmentObject;
  }

}
