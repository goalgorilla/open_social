<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines test steps around the usage of topics.
 */
class TopicContext extends RawMinkContext {

  private const CREATE_PAGE = "/node/add/topic";

  /**
   * Keep track of the topics that were created.
   *
   * This allows us to clean up at the end of the scenario. The array contains
   * the ID if we already have it in the step or the title otherwise. We avoid
   * looking up the topic because a user may be testing an error state.
   *
   * @var array
   * @phpstan-var array<int|string>
   */
  private array $created = [];

  /**
   * Topic data that was changed in a previous step.
   *
   * @phpstan-var array<string, mixed>
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
      elseif ($field === "published" && !$value) {
        $this->minkContext->assertPageContainsText("Unpublished");
      }
      else {
        $this->minkContext->assertPageContainsText($value);
      }
    }
  }

  /**
   * Clean up any topics created in this scenario.
   *
   * @AfterScenario
   */
  public function cleanUpTopics() : void {
    foreach ($this->created as $idOrTitle) {
      $nid = is_int($idOrTitle) ? $idOrTitle : $this->getTopicIdFromTitle($idOrTitle);
      if ($nid !== NULL) {
        // Ignore already deleted nodes, they may have been deleted in the test.
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
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $topic['uid'] = is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($topic['author']);
      if ($account->id() !== 0) {
        $topic['uid'] = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $topic['author']));
      }
    }
    unset($topic['author']);

    $topic['type'] = 'topic';

    if (isset($topic['field_topic_type'])) {
      $type_id = $this->getTopicTypeIdFromLabel($topic['field_topic_type']);
      if ($type_id === NULL) {
        throw new \Exception("Topic Type with label '{$topic['field_topic_type']}' does not exist.");
      }
      $topic['field_topic_type'] = $type_id;
    }

    $topic_object = Node::create($topic);
    $violations = $topic_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The topic you tried to create is invalid: $violations");
    }
    $topic_object->save();

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
  private function getTopicIdFromTitle($topic_title) : ?int {
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'topic')
      ->condition('title', $topic_title);

    $topic_ids = $query->execute();
    $topics = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($topic_ids);

    if (count($topics) > 1) {
      return NULL;
    }

    $topic = reset($topics);
    if ($topic->id() !== 0) {
      return $topic->id();
    }

    return NULL;
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

    if (count($terms) > 1) {
      return NULL;
    }

    $term = reset($terms);
    if ($term->id() !== 0) {
      return $term->id();
    }

    return NULL;
  }

}
