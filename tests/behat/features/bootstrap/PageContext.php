<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;

/**
 * Defines test steps around the usage of basic pages.
 */
class PageContext extends RawMinkContext {

  use EntityTrait;

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

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Create multiple pages at the start of a test.
   *
   * Creates page provided in the form, for example:
   *
   * Given pages:
   *   | title    | body            | author   | status |
   *   | My title | My description  | username | 1      |
   *   | ...      | ...             | ...      | ...    |
   *
   * @Given pages:
   */
  public function createPages(TableNode $pagesTable) : void {
    foreach ($pagesTable->getHash() as $pageHash) {
      $this->pageCreate($pageHash);
    }
  }

  /**
   * Create multiple topics at the start of a test.
   *
   * Creates page provided in the form, for example:
   *
   * Given pages with non-anonymous author:
   *   | title    | body            | author   | status |
   *   | My title | My description  | username | 1      |
   *   | ...      | ...             | ...      | ...    |
   *
   * @Given pages with non-anonymous author:
   */
  public function createPagesWithAuthor(TableNode $pagesTable) : void {
    // Create a new random user to own the content, this ensures the author
    // isn't anonymous.
    $user = (object) [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'role' => "authenticated",
    ];
    $user->mail = "{$user->name}@example.com";

    $this->drupalContext->userCreate($user);

    foreach ($pagesTable->getHash() as $pagesHash) {
      if (isset($pagesHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'pages with non-anonymous owner:' step, use 'pages:' instead.");
      }

      $pagesHash['author'] = $user->name;

      $this->pageCreate($pagesHash);
    }
  }

  /**
   * Create multiple topics at the start of a test.
   *
   * Creates page provided in the form, for example:
   *
   * Given pages with non-anonymous author:
   *   | title    | body            | author   | status |
   *   | My title | My description  | username | 1      |
   *   | ...      | ...             | ...      | ...    |
   *
   * @Given pages authored by current user:
   */
  public function createPagesAuthoredByCurrentUser(TableNode $pagesTable) : void {
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
    foreach ($pagesTable->getHash() as $pageHash) {
      if (isset($pageHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'pages authored by current user:' step, use 'pages:' instead.");
      }

      $pageHash['author'] = (is_object($current_user) ? $current_user->name : NULL) ?? 'anonymous';

      $this->pageCreate($pageHash);
    }
  }

  /**
   * Create a page.
   *
   * @return \Drupal\node\Entity\Node
   *   The page values.
   */
  private function pageCreate($page) : Node {
    if (!isset($page['author'])) {
      throw new \Exception("You must specify an `author` when creating a page. Specify the `author` field if using `@Given pages:` or use one of `@Given pages with non-anonymous author:` or `@Given pages authored by current user:` instead.");
    }

    $account = user_load_by_name($page['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $page['author']));
    }
    $page['uid'] = $account->id();
    unset($page['author']);

    if (isset($page['group'])) {
      $group_id = $this->getNewestGroupIdFromTitle($page['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$page['group']}' does not exist.");
      }
      unset($page['group']);
    }

    $page['type'] = 'page';

    $this->validateEntityFields("node", $page);
    $page_object = Node::create($page);
    $violations = $page_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The page you tried to create is invalid: $violations");
    }
    if (!$page_object->body->format) {
      $page_object->body->format = 'basic_html';
    }
    $page_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        Group::load($group_id)?->addContent($page_object, "group_node:page");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $page_object;
  }

}
