<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Defines test steps around the usage of books.
 */
class BookContext extends RawMinkContext {

  private const CREATE_PAGE = "/node/add/book";

  /**
   * Keep track of what we created, so we can use them for validating.
   */
  private array $created = [];

  /**
   * Provide help filling in the WYSIWYG editor.
   */
  private CKEditorContext $cKEditorContext;

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

    $this->cKEditorContext = $environment->getContext(CKEditorContext::class);
    $this->minkContext = $environment->getContext(SocialMinkContext::class);
  }

  /**
   * Fill out the book creation form and submit.
   *
   * Example: When I create a book using its creation page:
   *              | Title       | My Book Page  |
   *              | Description | It's the best |
   * Example: And create a book using its creation page:
   *              | Title       | My Book Page  |
   *              | Description | It's the best |
   *
   * @When /^(?:|I )create a book using its creation page:$/
   */
  public function whenICreateABookUsingTheForm(TableNode $fields): void {
    // Go to the form.
    $this->visitPath(self::CREATE_PAGE);
    $page = $this->getSession()->getPage();

    // Fill in the fields.
    $book = [];
    foreach ($fields->getRowsHash() as $field => $value) {
      $key = strtolower($field);
      $book[$key] = $value;
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
      // For the description we're using CKEditor so we must fill in the editor
      // rather than the hidden form field.
      // @todo Not being able to click the label shows an a11y issue.
      elseif ($key === "description") {
        $this->cKEditorContext->iFillInTheWysiwygEditor($field, $value);
      }
      else {
        $page->fillField($field, $value);
      }

    }

    // Submit the page.
    $page->pressButton("Create book");

    $this->created[] = $book;
  }

  /**
   * Check that a book that was just created is properly shown.
   *
   * @Then /^(?:|I )should see the book I just created$/
   */
  public function thenIShouldSeeTheBookIJustCreated() : void {
    $regions = [
      'title' => "Hero block",
      'description' => 'Main content',
    ];

    $fields = end($this->created);

    $this->minkContext->assertPageContainsText("Book page {$fields['title']} has been created.");

    foreach ($fields as $field => $value) {
      if (isset($regions[$field])) {
        $this->minkContext->assertRegionText($value, $regions[$field]);
      }
      else {
        $this->minkContext->assertPageContainsText($value);
      }
    }
  }

  /**
   * Check that author information is not shown.
   *
   * @Then it should not show author information
   */
  public function  itShouldNotShowAuthorInformation() : void {
    $this->minkContext->assertNotRegionText("By", "Hero block");
    $this->minkContext->assertNotRegionText(" on ", "Hero block");
  }

}
