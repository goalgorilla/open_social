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
 * Defines test steps around the usage of events.
 */
class EventContext extends RawMinkContext {

  use EntityTrait;
  use NodeTrait;
  use GroupTrait;

  private const CREATE_PAGE = "/node/add/event";

  /**
   * Keep track of the events that were created.
   *
   * This allows us to clean up at the end of the scenario. The array contains
   * the ID if we already have it in the step or the title otherwise. We avoid
   * looking up the event because a user may be testing an error state.
   *
   * @var array
   * @phpstan-var array<int|string>
   */
  private array $created = [];

  /**
   * Event data that was changed in a previous step.
   *
   * @phpstan-var array<string, mixed>
   */
  private array $updatedEventData = [];

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
   * View the event overview.
   *
   * @Given I am on the event overview
   */
  public function viewEventOverview() : void {
    $this->visitPath("/all-events");
  }

  /**
   * View a specific event.
   *
   * @When I am viewing the event :event
   * @When am viewing the event :event
   */
  public function viewingEvent(string $event) : void {
    $event_id = $this->getEventIdFromTitle($event);
    if ($event_id === NULL) {
      throw new \Exception("Event '${event}' does not exist.");
    }
    $this->visitPath("/node/${event_id}");
  }

  /**
   * Edit a specific event.
   *
   * @When I am editing the event :event
   * @When am editing the event :event
   */
  public function editingEvent(string $event) : void {
    $event_id = $this->getEventIdFromTitle($event);
    if ($event_id === NULL) {
      throw new \Exception("Event '${event}' does not exist.");
    }
    $this->visitPath("/node/${event_id}/edit");
  }

  /**
   * Create multiple events at the start of a test.
   *
   * Creates events provided in the form:
   * | title    | body            | author   | field_content_visibility | field_event_type | language  | status |
   * | My title | My description  | username | public                   | News             | en        | 1         |
   * | ...      | ...             | ...      | ...                      | ...              | ...       |
   *
   * @Given events:
   */
  public function createEvents(TableNode $eventsTable) : void {
    foreach ($eventsTable->getHash() as $eventHash) {
      $event = $this->eventCreate($eventHash);
      $this->created[] = $event->id();
    }
  }

  /**
   * Fill out the event creation form and submit.
   *
   * Example: When I create a event using its creation page:
   *              | Title       | Llama |
   *              | Description | Llama's are really misunderstood animals. |
   * Example: And create a event using its creation page:
   *              | Title       | Cheese |
   *              | Description | There are all kinds of cheese <3 |
   *
   * @When /^(?:|I )create a event using its creation page:$/
   */
  public function whenICreateAEventUsingTheForm(TableNode $fields) : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->updatedEventData = $this->fillOutEventForm($fields);
    $this->getSession()->getPage()->pressButton("Create event");
    $this->created[] = $this->updatedEventData['title'];
  }

  /**
   * View the event creation page.
   *
   * @When /^(?:|I )view the event creation page$/
   */
  public function whenIViewTheEventCreationPage() : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Fill out the event edit form and submit.
   *
   * Example: When I edit event "Some Event" using its edit page:
   *              | Title       | New Title |
   *
   * @When /^(?:|I )edit event "(?P<title>(?:[^"]|\\")*)" using its edit page:$/
   */
  public function whenIEditEventUsingTheForm(string $title, TableNode $fields) : void {
    $event_id = $this->getEventIdFromTitle($title);
    if ($event_id === NULL) {
      throw new \Exception("Event with title '${title}' does not exist. Did you create it in the test?");
    }
    $this->visitPath("/node/${event_id}/edit");

    $this->minkContext->saveScreenshot("edit-event.png", "/var/www/html/profiles/contrib/social/tests/behat/logs");

    $this->updatedEventData = $this->fillOutEventForm($fields);
    $this->getSession()->getPage()->pressButton("Save");

    // If the title wasn't updated then we want to add the original title to our
    // data so that it can be used in checking the update message.
    if (!isset($this->updatedEventData['title'])) {
      $this->updatedEventData['title'] = $title;
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
  protected function fillOutEventForm(TableNode $fields) : array {
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
   * Check that a event that was just created is properly shown.
   *
   * @Then /^(?:|I )should see the event I just (?P<action>(created|updated))$/
   */
  public function thenIShouldSeeTheEventIJustUpdated(string $action) : void {
    $regions = [
      'title' => "Hero block",
      'description' => 'Main content',
    ];

    $this->minkContext->assertPageContainsText("Event {$this->updatedEventData['title']} has been $action.");

    foreach ($this->updatedEventData as $field => $value) {
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
   * Assert that we landed on the event creation form.
   *
   * @Then I should be on the event creation form
   */
  public function shouldBeOnEventCreationForm() : void {
    $status_code = $this->getSession()->getStatusCode();
    if ($status_code !== 200) {
      throw new \Exception("The page status code {$status_code} dis not match 200 Ok.");
    }

    $this->minkContext->assertPageContainsText("Create an event");
  }

  /**
   * Clean up any events created in this scenario.
   *
   * @AfterScenario
   */
  public function cleanUpEvents() : void {
    foreach ($this->created as $idOrTitle) {
      // Drupal's `id` method can return integers typed as string (e.g. `"1"`).
      $nid = is_numeric($idOrTitle) ? $idOrTitle : $this->getEventIdFromTitle($idOrTitle);
      // Ignore already deleted nodes, they may have been deleted in the test.
      if ($nid !== NULL) {
        Node::load($nid)?->delete();
      }
    }
  }

  /**
   * Create a event.
   *
   * @return \Drupal\node\Entity\Node
   *   The event values.
   */
  private function eventCreate($event) : Node {
    if (!isset($event['author'])) {
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $event['uid'] = is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($event['author']);
      if ($account->id() !== 0) {
        $event['uid'] = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $event['author']));
      }
    }
    unset($event['author']);

    if (isset($event['group'])) {
      $group_id = $this->getNewestGroupIdFromTitle($event['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$event['group']}' does not exist.");
      }
      unset($event['group']);
    }

    $event['type'] = 'event';

    if (isset($event['field_event_type'])) {
      $type_id = $this->getEventTypeIdFromLabel($event['field_event_type']);
      if ($type_id === NULL) {
        throw new \Exception("Event Type with label '{$event['field_event_type']}' does not exist.");
      }
      $event['field_event_type'] = $type_id;
    }

    $this->validateEntityFields("node", $event);
    $event_object = Node::create($event);
    $violations = $event_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The event you tried to create is invalid: $violations");
    }
    $event_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        Group::load($group_id)?->addContent($event_object, "group_node:event");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $event_object;
  }

  /**
   * Get the event from a event title.
   *
   * @param string $event_title
   *   The title of the event.
   *
   * @return int|null
   *   The integer ID of the event or NULL if no event could be found.
   */
  private function getEventIdFromTitle(string $event_title) : ?int {
    return $this->getNodeIdFromTitle("event", $event_title);
  }

  /**
   * Get the Term ID for a event type from its label.
   *
   * @param string $label
   *   The label.
   *
   * @return int|null
   *   The event type ID or NULL if it can't be found.
   */
  private function getEventTypeIdFromLabel(string $label) : ?int {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('vid', 'event_types')
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
