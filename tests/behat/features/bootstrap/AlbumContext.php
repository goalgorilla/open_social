<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Defines test steps around the usage of albums.
 */
class AlbumContext extends RawMinkContext {

  use NodeTrait;
  use GroupTrait;

  private const CREATE_PAGE = "/node/add/album";

  /**
   * Keep track of what we created, so we can use them for validating.
   */
  private array $created = [];

  /**
   * The Drupal mink context is useful for validation of content.
   */
  private MinkContext $minkContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->minkContext = $environment->getContext(SocialMinkContext::class);
  }

  /**
   * Check that an album that was just created is properly shown.
   *
   * @Then /^(?:|I )should see the album I just created$/
   */
  public function thenIShouldSeeTheAlbumIJustCreated() : void {
    $fields = end($this->created);

    $this->minkContext->assertPageContainsText("Album {$fields['title']} is successfully created. Now you can add images to this album.");
  }

  /**
   * Fill out the album creation form and submit.
   *
   * Example: When I create an album using its creation page:
   *              | Title                          | My Album  |
   *              | visibility                     | public |
   *              | group                          | My group |
   *
   * @When /^(?:|I )create an album using its creation page:$/
   */
  public function whenICreateAnAlbumUsingTheForm(TableNode $fields): void {
    // Go to the form.
    $this->visitPath(self::CREATE_PAGE);
    $page = $this->getSession()->getPage();

    // Fill in the fields.
    $album = [];
    foreach ($fields->getRowsHash() as $field => $value) {
      $key = strtolower($field);
      $album[$key] = $value;
      // We must be more specific for the title field since there could be more
      // than one on the page (e.g. the menu title).
      if ($key === "title") {
        $fieldset = $page
          ->find("named", ["fieldset", "Basic information"]);

        if ($fieldset === NULL) {
          throw new ElementNotFoundException($this->getSession()->getDriver(), "fieldset", "named", "Basic Information");
        }

        $fieldset->fillField($field, $value);
      }
      elseif ($key === "group") {
        $group_id = $this->getNewestGroupIdFromTitle($value);
        if ($group_id === NULL) {
          throw new \Exception("Group '${$value}' does not exist.");
        }
        $page->selectFieldOption($field, $group_id);
        // Changing the group of an album updates the visibility settings so we
        // must wait for that to be complete.
        $this->minkContext->iWaitForAjaxToFinish();
      }
      elseif ($key === "visibility") {
        $page->selectFieldOption("field_content_visibility", $value);
      }
      else {
        $page->fillField($field, $value);
      }

      // Changing the group of an album updates the visibility settings so we must
      // wait for that to be complete.
      if ($key === "group") {
        $this->minkContext->iWaitForAjaxToFinish();
      }
    }

    // Submit the page.
    $page->pressButton("Create album");

    $this->created[] = $album;
  }
}
