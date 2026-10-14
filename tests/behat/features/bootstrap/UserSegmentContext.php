<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\user_segments\Entity\UserSegment;

/**
 * Defines test steps around the usage of user segments.
 */
class UserSegmentContext extends RawMinkContext {

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
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();

    $this->minkContext = $environment->getContext(SocialMinkContext::class);
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
   * Fill out the user segment creation form and submit.
   *
   * Example: When I create a user segment using its creation page:
   *              | Label       | My Test Segment  |
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

      // Handle different field types.
      if ($key === "label") {
        $page->fillField("Segment name", $value);
      }
      elseif ($key === "status") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $page->checkField("Enabled");
        }
        else {
          $page->uncheckField("Enabled");
        }
      }
      elseif ($key === "visibility_option") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $page->checkField("Use this segment as a visibility option");
        }
        else {
          $page->uncheckField("Use this segment as a visibility option");
        }
      }
      elseif ($key === "visibility_option_label") {
        $page->fillField("A custom name for visibility settings", $value);
      }
      elseif ($key === "rules") {
        $id = "user-segment-value";
        // Skipping fillField() for "rules" since the field is inside a
        // container with display: none, which makes it inaccessible to Mink's
        // standard form interaction methods.
        $javascript = "document.getElementById('$id').value = JSON.stringify($value);";
        $this->getSession()->executeScript($javascript);
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
    $this->minkContext->assertPageContainsText("New segment {$this->lastCreatedValues['label']} has been created.");

    foreach ($this->lastCreatedValues as $field => $value) {
      if ($field === "label") {
        $this->minkContext->assertPageContainsText($value);
      }
      elseif ($field === "status") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $this->minkContext->assertPageContainsText("Enabled");
        }
        else {
          $this->minkContext->assertPageContainsText("Disabled");
        }
      }
      elseif ($field === "visibility_option") {
        if ($value == '1' || $value === 'TRUE' || $value === 'true') {
          $this->minkContext->assertPageContainsText("Yes");
        }
        else {
          $this->minkContext->assertPageContainsText("No");
        }
      }
      elseif ($field === "visibility_option_label") {
        $this->minkContext->assertPageContainsText($value);
      }
      elseif ($field === "rules") {
        // The rules field is not displayed on user segment entity view.
        continue;
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

}
