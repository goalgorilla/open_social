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
use Symfony\Component\Yaml\Yaml;

/**
 * Defines test steps around the usage of topics.
 */
class TopicContext extends RawMinkContext {

  use EntityTrait;
  use NodeTrait;
  use GroupTrait;

  private const CREATE_PAGE = "/node/add/topic";

  /**
   * Keep track of the topics that were created.
   *
   * This allows us to clean up at the end of the scenario. The array contains
   * the ID if we already have it in the step or the title otherwise. We avoid
   * looking up the topic because a user may be testing an error state.
   *
   * @var array<int|string>
   */
  private array $created = [];

  /**
   * Topic data that was changed in a previous step.
   *
   * @var array<string, mixed>
   */
  private array $updatedTopicData = [];

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
   * View the topic overview.
   *
   * @Given I am on the topic overview
   */
  public function viewTopicOverview() : void {
    $this->visitPath("/all-topics");
  }

  /**
   * View a specific topic.
   *
   * @When I am viewing the topic :topic
   * @When am viewing the topic :topic
   */
  public function viewingTopic(string $topic) : void {
    $topic_id = $this->getTopicIdFromTitle($topic);
    if ($topic_id === NULL) {
      throw new \Exception("Topic '${topic}' does not exist.");
    }
    $this->visitPath("/node/${topic_id}");
  }

  /**
   * Edit a specific topic.
   *
   * @When I am editing the topic :topic
   * @When am editing the topic :topic
   */
  public function editingTopic(string $topic) : void {
    $topic_id = $this->getTopicIdFromTitle($topic);
    if ($topic_id === NULL) {
      throw new \Exception("Topic '${topic}' does not exist.");
    }
    $this->visitPath("/node/${topic_id}/edit");
  }

  /**
   * Create multiple topics at the start of a test.
   *
   * Creates topics provided in the form:
   * | title    | body            | author   | field_content_visibility | field_topic_type | language  | status |
   * | My title | My description  | username | public                   | News             | en        | 1         |
   * | ...      | ...             | ...      | ...                      | ...              | ...       |
   *
   * @Given topics:
   */
  public function createTopics(TableNode $topicsTable) : void {
    foreach ($topicsTable->getHash() as $topicHash) {
      $topic = $this->topicCreate($topicHash);
      $this->created[] = $topic->id();
    }
  }

  /**
   * Create multiple topics at the start of a test.
   *
   * Creates topics provided in the form:
   * | title    | body            | field_content_visibility | field_topic_type | language  | status |
   * | My title | My description  | public                   | News             | en        | 1         |
   * | ...      | ...             | ...                      | ...              | ...       |
   *
   * @Given topics with non-anonymous author:
   */
  public function createTopicsWithAuthor(TableNode $topicsTable) : void {
    // Create a new random user to own the content, this ensures the author
    // isn't anonymous.
    $user = (object) [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'role' => "authenticated",
    ];
    $user->mail = "{$user->name}@example.com";

    $this->drupalContext->userCreate($user);

    foreach ($topicsTable->getHash() as $topicHash) {
      if (isset($topicHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'topics with non-anonymous owner:' step, use 'topics:' instead.");
      }

      $topicHash['author'] = $user->name;

      $topic = $this->topicCreate($topicHash);
      $this->created[] = $topic->id();
    }
  }

  /**
   * Create multiple topics at the start of a test.
   *
   * Creates topics provided in the form:
   * | title    | body            | field_content_visibility | field_topic_type | language  | status |
   * | My title | My description  | public                   | News             | en        | 1         |
   * | ...      | ...             | ...                      | ...              | ...       |
   *
   * @Given topics authored by current user:
   */
  public function createTopicsAuthoredByCurrentUser(TableNode $topicsTable) : void {
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
    foreach ($topicsTable->getHash() as $topicHash) {
      if (isset($topicHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'topics authored by current user:' step, use 'topics:' instead.");
      }

      $topicHash['author'] = (is_object($current_user) ? $current_user->name : NULL) ?? 'anonymous';

      $topic = $this->topicCreate($topicHash);
      $this->created[] = $topic->id();
    }
  }

  /**
   * Fill out the topic creation form and submit.
   *
   * Example: When I create a topic using its creation page:
   *              | Title       | Llama |
   *              | Description | Llama's are really misunderstood animals. |
   * Example: And create a topic using its creation page:
   *              | Title       | Cheese |
   *              | Description | There are all kinds of cheese <3 |
   *
   * @When /^(?:|I )create a topic using its creation page:$/
   */
  public function whenICreateATopicUsingTheForm(TableNode $fields) : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->updatedTopicData = $this->fillOutTopicForm($fields);
    $this->getSession()->getPage()->pressButton("Create topic");
    $this->created[] = $this->updatedTopicData['title'];
  }

  /**
   * View the topic creation page.
   *
   * @When /^(?:|I )view the topic creation page$/
   */
  public function whenIViewTheTopicCreationPage() : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Fill out the topic edit form and submit.
   *
   * Example: When I edit topic "Some Topic" using its edit page:
   *              | Title       | New Title |
   *
   * @When /^(?:|I )edit topic "(?P<title>(?:[^"]|\\")*)" using its edit page:$/
   */
  public function whenIEditTopicUsingTheForm(string $title, TableNode $fields) : void {
    $topic_id = $this->getTopicIdFromTitle($title);
    if ($topic_id === NULL) {
      throw new \Exception("Topic with title '${title}' does not exist. Did you create it in the test?");
    }
    $this->visitPath("/node/${topic_id}/edit");

    $this->minkContext->saveScreenshot("edit-topic.png", "/var/www/html/profiles/contrib/social/tests/behat/logs");

    $this->updatedTopicData = $this->fillOutTopicForm($fields);
    $this->getSession()->getPage()->pressButton("Save");

    // If the title wasn't updated then we want to add the original title to our
    // data so that it can be used in checking the update message.
    if (!isset($this->updatedTopicData['title'])) {
      $this->updatedTopicData['title'] = $title;
    }
  }

  /**
   * Fill out the book create or edit field on the page.
   *
   * @param \Behat\Gherkin\Node\TableNode $fields
   *   The fields as provided by the Gherkin step.
   *
   * @return array
   *   The array of normalised data, the keys are lowercase field names,
   *   the values are as they would be stored in the database.
   */
  protected function fillOutTopicForm(TableNode $fields) : array {
    $normalized_data = [];
    $page = $this->getSession()->getPage();
    foreach ($fields->getRowsHash() as $field => $value) {
      $key = strtolower($field);
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
      elseif ($key === "type") {
        $page->checkField($value);
      }
      elseif ($key === "published") {
        $element = $page->findField($field);
        if ($element === NULL) {
          throw new ElementNotFoundException($this->getSession()->getDriver(), "field", NULL, $field);
        }
        // This field could be in a collapsed details element that we must
        // expand.
        if (!$element->isVisible()) {
          do {
            $element = $element->getParent();
            if ($element->getTagName() === "body") {
              throw new \Exception("${field} was not visible but could not find a parent 'details' element to expand.");
            }
          } while ($element->getTagName() !== "details");
          if ($element->hasAttribute("open")) {
            throw new \Exception("${field} was in an open details element but was still not visible.");
          }
          $summary = $element->find('named', 'summary');
          if ($summary === NULL) {
            throw new \Exception("${field} was in a closed details element but the details element did not contain a summary to expand it.");
          }
          // This should expand the details so that we can check the field.
          $summary->click();
        }

        // Convert from Yaml to allow `false`, `no`, `0`, `yes`, etc.
        $value = (bool) Yaml::parse($value);
        if ($value) {
          $page->checkField($field);
        }
        else {
          $page->uncheckField($field);
        }
      }
      else {
        $page->fillField($field, $value);
      }

      $normalized_data[$key] = $value;
    }

    return $normalized_data;
  }

  /**
   * Check that a topic that was just created is properly shown.
   *
   * @Then /^(?:|I )should see the topic I just (?P<action>(created|updated))$/
   */
  public function thenIShouldSeeTheTopicIJustUpdated(string $action) : void {
    $regions = [
      'title' => "Hero block",
      'description' => 'Main content',
    ];

    $this->minkContext->assertPageContainsText("Topic {$this->updatedTopicData['title']} has been $action.");

    foreach ($this->updatedTopicData as $field => $value) {
      if (isset($regions[$field])) {
        $this->minkContext->assertRegionText($value, $regions[$field]);
      }
      // We need a special case for value since we need to check for a string.
      elseif ($field === "published") {
        if (!$value) {
          $this->minkContext->assertPageContainsText("Unpublished");
        }
        else {
          $this->minkContext->assertPageNotContainsText("Unpublished");
        }
      }
      else {
        $this->minkContext->assertPageContainsText($value);
      }
    }
  }

  /**
   * Assert that we landed on the topic creation form.
   *
   * @Then I should be on the topic creation form
   */
  public function shouldBeOnTopicCreationForm() : void {
    $status_code = $this->getSession()->getStatusCode();
    if ($status_code !== 200) {
      throw new \Exception("The page status code {$status_code} dis not match 200 Ok.");
    }

    $this->minkContext->assertPageContainsText("Create a topic");
  }

  /**
   * Clean up any topics created in this scenario.
   *
   * @AfterScenario
   */
  public function cleanUpTopics() : void {
    foreach ($this->created as $idOrTitle) {
      // Drupal's `id` method can return integers typed as string (e.g. `"1"`).
      $nid = is_numeric($idOrTitle) ? $idOrTitle : $this->getTopicIdFromTitle($idOrTitle);
      // Ignore already deleted nodes, they may have been deleted in the test.
      if ($nid !== NULL) {
        Node::load($nid)?->delete();
      }
    }
  }

  /**
   * Create a topic.
   *
   * @return \Drupal\node\Entity\Node
   *   The topic values.
   */
  private function topicCreate($topic) : Node {
    if (!isset($topic['author'])) {
      throw new \Exception("You must specify an `author` when creating a topic. Specify the `author` field if using `@Given topics:` or use one of `@Given topics with non-anonymous author:` or `@Given topics authored by current user:` instead.");
    }

    $account = user_load_by_name($topic['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $event['author']));
    }
    $topic['uid'] = $account->id();
    unset($topic['author']);

    if (isset($topic['group'])) {
      $group_id = $this->getNewestGroupIdFromTitle($topic['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$topic['group']}' does not exist.");
      }
      unset($topic['group']);
    }

    $topic['type'] = 'topic';

    if (isset($topic['field_topic_type'])) {
      $type_id = $this->getTopicTypeIdFromLabel($topic['field_topic_type']);
      if ($type_id === NULL) {
        throw new \Exception("Topic Type with label '{$topic['field_topic_type']}' does not exist.");
      }
      $topic['field_topic_type'] = $type_id;
    }

    $this->validateEntityFields("node", $topic);
    $topic_object = Node::create($topic);
    $violations = $topic_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The topic you tried to create is invalid: $violations");
    }
    $topic_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        Group::load($group_id)?->addContent($topic_object, "group_node:topic");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $topic_object;
  }

  /**
   * Get the topic from a topic title.
   *
   * @param string $topic_title
   *   The title of the topic.
   *
   * @return int|null
   *   The integer ID of the topic or NULL if no topic could be found.
   */
  private function getTopicIdFromTitle(string $topic_title) : ?int {
    return $this->getNodeIdFromTitle("topic", $topic_title);
  }

  /**
   * Get the Term ID for a topic type from its label.
   *
   * @param string $label
   *   The label.
   *
   * @return int|null
   *   The topic type ID or NULL if it can't be found.
   */
  private function getTopicTypeIdFromLabel(string $label) : ?int {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('vid', 'topic_types')
      ->condition('name', $label);

    $term_ids = $query->execute();
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($term_ids);

    if (count($terms) !== 1) {
      return NULL;
    }

    $term_id = reset($terms)->id();
    if ($term_id !== 0) {
      return $term_id;
    }

    return NULL;
  }

}
