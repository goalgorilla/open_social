<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\social_post\Entity\Post;

/**
 * Defines application features related to post.
 */
class PostContext extends RawMinkContext {

  use EntityTrait;

  /**
   * Keep track of all posts that are created so they can easily be removed.
   */
  private array $posts = [];

  // TODO: Keep track of created posts and clean them up after the fact.
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
   * Create multiple posts at the start of a test.
   *
   * Creates post of a given type provided in the form:
   * | field_post     | type | field_visibility | status | langcode
   * | My description | post | 0                | 1      | en
   * | ...            | ...  | ...              | ...    | ...
   *
   * @Given posts with non-anonymous owner:
   */
  public function createGroupsWithOwner(TableNode $postsTable) {
    // Create a new random user to own our groups, this ensures the author
    // isn't anonymous.
    $user = (object) [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'role' => "authenticated",
    ];
    $user->mail = "{$user->name}@example.com";

    $this->drupalContext->userCreate($user);

    foreach ($postsTable->getHash() as $postHash) {
      if (isset($postHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'posts with non-anonymous owner:' step, use 'posts:' instead.");
      }

      // We specify the owner for each post to be the current user.
      // `postCreate` will load the user by name.
      $postHash['author'] = $user->name;

      $post = $this->postCreate($postHash);
      $this->posts[$post->id()] = $post;
    }
  }

  /**
   * Select a post visibility.
   *
   * @When I select post visibility :visibility
   */
  public function iSelectPostVisibility($visibility) {
    // First make post visibility setting visible.
    $this->iClickPostVisibilityDropdown();

    // Click the label of the readio button with the visibility. The radio
    // button itself can't be clicked because it's invisible.
    $page = $this->getSession()->getPage();
    $field = $page->findField($visibility);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $visibility);
    }

    $field->getParent()->click();
  }

  /**
   * Open the post visibility dropdown.
   *
   * @When /^I click the post visibility dropdown/
   */
  public function iClickPostVisibilityDropdown() {
    $locator = 'button#post-visibility';
    $session = $this->getSession();
    $element = $session->getPage()->find('css', $locator);

    if ($element === NULL) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    // Now click the element.
    $element->click();
  }

  /**
   * Clean up posts created in scenarios.
   *
   * @AfterScenario
   */
  public function cleanupPost(AfterScenarioScope $scope) {
    if (!empty($this->posts)) {
      foreach ($this->posts as $post) {
        $post->delete();
      }
    }

    $query = \Drupal::entityQuery('post')
      ->condition(
        'field_post',
        [
          'This is a public post.',
          'This is a community post.',
        ],
        'IN'
      );

    $post_ids = $query->execute();

    $posts = \Drupal::entityTypeManager()->getStorage('post')->loadMultiple($post_ids);

    foreach ($posts as $post) {
      $post->delete();
    }
  }

  /**
   * Create a post.
   *
   * @param array $post
   *   The values to pass to Post::create. `author` can be set to a username
   *   which will be converted to a uid.
   *
   * @return \Drupal\social_post\Entity\Post
   *   The created post.
   */
  private function postCreate(array $post) : Post {
    if (!isset($post['author'])) {
      throw new \Exception("You must specify an `author` when creating a post. Specify the `author` field if using `@Given posts:` or use one of `@Given posts with non-anonymous owner:` or `@Given posts owned by current user:` instead.");
    }

    $account = user_load_by_name($post['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $post['author']));
    }
    $post['user_id'] = $account->id();
    unset($post['author']);

    // Let's create some groups.
    $this->validateEntityFields('post', $post);
    $post_object = Post::create($post);
    $violations = $post_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The post you tried to create is invalid: $violations");
    }
    $post_object->save();

    return $post_object;
  }

}
