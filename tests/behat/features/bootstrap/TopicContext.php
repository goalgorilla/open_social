<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines test steps around the usage of topics.
 */
class TopicContext extends RawMinkContext {

  private const CREATE_PAGE = "/node/add/topic";

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
   * The test bridge that allows running code in the Drupal installation.
   */
  private TestBridgeContext $testBridge;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->cKEditorContext = $environment->getContext(CKEditorContext::class);
    $this->minkContext = $environment->getContext(SocialMinkContext::class);
    $this->testBridge = $environment->getContext(TestBridgeContext::class);
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
   * Creates a large number of topics.
   *
   * @Given :count topics with title :title by :username
   */
  public function massCreateTopics(int $count, string $title, string $username) : void {
    $topics = [];
    for ($index = 1; $index <= $count; $index++) {
      $topics[] = [
        'author' => $username,
        'title' => str_replace('[id]', (string) $index, $title),
        'body' => "foobar",
        'field_topic_type' => "News",
        'field_content_visibility' => "public",
        'created' => "+$index minutes",
        'changed' => "+$index minutes",
      ];
    }

    $response = $this->testBridge->command(
      "create-topics",
      topics: $topics,
    );
    $this->assertEntityCreationSuccessful("topics", $response);
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
   * Get the topic from a topic title.
   *
   * @param string $topic_title
   *   The title of the topic.
   *
   * @return int|null
   *   The integer ID of the topic or NULL if no topic could be found.
   */
  private function getTopicIdFromTitle(string $topic_title) : ?int {
    $response = $this->testBridge->command(
      "topic-id-from-title",
      title: $topic_title
    );
    if (!isset($response['id'])) {
      throw new \RuntimeException("Missing 'id' in response from test bridge: " . json_encode($response));
    }
    return $response['id'];
  }

  /**
   * Ensure entity creation did not have any errors.
   *
   * @param array $response
   *   The response provided by the test bridge.
   *
   * @throws \RuntimeException
   *   In case the bridge provided unexpected output.
   * @throws \InvalidArgumentException
   *   In case creation failed due to invalid input.
   */
  private function assertEntityCreationSuccessful(string $entity_type, array $response) : void {
    if (isset($response['status'], $response['error']) && $response['error'] === "Command 'create-$entity_type' not found") {
      throw new \InvalidArgumentException("There's no bridge command registered to create $entity_type. Expected command 'create-$entity_type' to be available.");
    }

    if (!isset($response['created'], $response['errors'])) {
      throw new \RuntimeException("Invalid response from test bridge: " . json_encode($response));
    }

    if ($response['errors'] !== []) {
      throw new \InvalidArgumentException("Could not create all requested entities: \n - " . implode("\n - ", $response['errors']));
    }
  }

}
