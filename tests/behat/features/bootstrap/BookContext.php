<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;

/**
 * Defines test steps around the usage of books.
 */
class BookContext extends RawMinkContext {

  use NodeTrait;
  use GroupTrait;

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
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->cKEditorContext = $environment->getContext(CKEditorContext::class);
    $this->minkContext = $environment->getContext(SocialMinkContext::class);
    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * View the book creation page.
   *
   * @When /^(?:|I )view the book creation page$/
   */
  public function whenIViewTheBookCreationPage() : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Create multiple books at the start of a test.
   *
   * Creates books provided in the form:
   * | title    | body            | author   | field_content_visibility |  language  | status |
   * | My title | My description  | username | public                   |  en        | 1      |
   * | ...      | ...             | ...      | ...                      |  ...       | ...    |
   *
   * @Given books:
   */
  public function createTopics(TableNode $topicsTable) : void {
    foreach ($topicsTable->getHash() as $bookHash) {
      $book = $this->bookCreate($bookHash);
      $this->created[] = $book->id();
    }
  }

  /**
   * Enable Drupal core book functionality for a content type.
   *
   * @Given book structure is enabled for :content_type
   */
  public function enableBookStructureForContentType(string $content_type) : void {
    $config = \Drupal::configFactory()->getEditable('book.settings');

    if ($config->isNew()) {
      throw new \Exception("The book.settings configuration did not yet exist, is the 'book' module enabled?");
    }

    $allowed_types = $config->get('allowed_types');
    $allowed_types[] = $content_type;

    $config->set('allowed_types', $allowed_types)->save();
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
      elseif ($key === "group") {
        $group_id = $this->getNewestGroupIdFromTitle($value);
        if ($group_id === NULL) {
          throw new \Exception("Group '${$value}' does not exist.");
        }
        $page->selectFieldOption($field, $group_id);
        // Changing the group of a book updates the visibility settings so we
        // must wait for that to be complete.
        $this->minkContext->iWaitForAjaxToFinish();
      }
      elseif ($key === "visibility") {
        $page->selectFieldOption("field_content_visibility", $value);
      }
      else {
        $page->fillField($field, $value);
      }

      // Changing the group of a book updates the visibility settings so we must
      // wait for that to be complete.
      if ($key === "group") {
        $this->minkContext->iWaitForAjaxToFinish();
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
      // We skip checking for the group, visibility or parent book because it's
      // not actually shown as information on book pages.
      // @todo We should let `thenIShouldSeeTheBookIJustCreated` take a list of fields to validate so the skipped fields are more explicit.
      if ($field === "group" || $field === "visibility" || $field === "book") {
        continue;
      }

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

  /**
   * Assert that we landed on the book creation form.
   *
   * @Then I should be on the book creation form
   */
  public function shouldBeOnBookCreationForm() : void {
    $status_code = $this->getSession()->getStatusCode();
    if ($status_code !== 200) {
      throw new \Exception("The page status code {$status_code} dis not match 200 Ok.");
    }

    $this->minkContext->assertPageContainsText("Create a book page");
  }

  /**
   * Create a book.
   *
   * @param array $book
   *   The book values.
   *
   * @return \Drupal\node\Entity\Node
   *   The created book.
   */
  private function bookCreate(array $book) : Node {
    // Filter out any empty strings that may be present due to the table format
    // of the input. We can't use an empty filter function because we want to
    // preserve things like `0`.
    $book = array_filter($book, fn ($val) => $val !== "");

    if (!isset($book['author'])) {
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $book['uid'] = is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($book['author']);
      if (!$account) {
        throw new \Exception("Could not load author");
      }
      if ($account->id() !== 0) {
        $book['uid'] = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $book['author']));
      }
    }
    unset($book['author']);

    if (isset($book['group'])) {
      $group_id = $this->getNewestGroupIdFromTitle($book['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$book['group']}' does not exist.");
      }
      unset($book['group']);
    }

    if (!isset($book['book']) || trim($book['book']) === "") {
      $book_id = 'new';
      if (isset($book['parent']) && trim($book['parent']) !== "") {
        throw new \Exception("Can not set property 'parent' without specifying 'book'.");
      }
    }
    else {
      $book_id = $this->getBookIdFromTitle($book['book']);
      if ($book_id === NULL) {
        throw new \Exception("Book '{$book['book']}' does not exist.");
      }
    }
    unset($book['book']);

    $book['type'] = 'book';

    $book_object = Node::create($book);
    $violations = $book_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The book you tried to create is invalid: $violations");
    }

    $book_object->book = \Drupal::service('book.manager')->getLinkDefaults($book_object);
    $book_object->book['bid'] = $book_id;

    if (!isset($book['parent']) && isset($book['book'])) {
      // A book can only have one top level book, so we automatically set the
      // parent.
      $book['parent'] = $book['book'];
    }
    if (isset($book['parent'])) {
      $parent_id = $this->getBookIdFromTitle($book['parent']);
      if ($parent_id === NULL) {
        throw new \Exception("Book '{$book['parent']}' does not exist.");
      }
      $book_object->book['pid'] = $parent_id;
    }

    $book_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        Group::load($group_id)?->addContent($book_object, "group_node:book");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $book_object;
  }

  /**
   * Get the book from a book title.
   *
   * @param string $title
   *   The title of the book.
   *
   * @return int|null
   *   The integer ID of the book or NULL if no book could be found.
   */
  private function getBookIdFromTitle(string $title) : ?int {
    return $this->getNodeIdFromTitle("book", $title);
  }

}
