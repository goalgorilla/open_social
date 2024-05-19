<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Crypt;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\Entity\Node\Event;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
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
   * @var array<int|string>
   */
  private array $created = [];

  /**
   * Event data that was changed in a previous step.
   *
   * @var array<string, mixed>
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
    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
    $this->testBridge = $environment->getContext(TestBridgeContext::class);
  }

  /**
   * View the event overview.
   *
   * @Given I am on the event overview
   */
  public function viewEventOverview() : void {
    $this->visitPath("/community-events");
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
   * Assert we're on the event page.
   *
   * Can be used to check that a redirect was implemented correctly.
   *
   * @Then I should be viewing the event :event
   * @Then should be viewing the event :event
   */
  public function shouldBeViewingEvent(string $event) : void {
    $event_id = $this->getEventIdFromTitle($event);
    if ($event_id === NULL) {
      throw new \Exception("Event '${event}' does not exist.");
    }
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals("/node/${event_id}");
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
   * View the event manager page for a specific event.
   *
   * @When I am viewing the event manager page for :event
   * @When am viewing the event manager page for :event
   */
  public function viewEventManagerPage(string $event) : void {
    $event_id = $this->getEventIdFromTitle($event);
    if ($event_id === NULL) {
      throw new \Exception("Event '${event}' does not exist.");
    }
    $this->visitPath("/node/${event_id}/all-enrollments");
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
   * Make yourself an event manager for an event.
   *
   * @Given I am an event manager for the :title event
   * @Given am an event manager for the :title event
   */
  public function iAmAnEventManagerForTheEvent(string $title) : void {
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();

    if ($current_user === NULL || !isset($current_user->uid) || $current_user->uid === 0) {
      throw new \RuntimeException("Can not make an anonymous user event manager");
    }

    $event_id = $this->getEventIdFromTitle($title);
    if ($event_id === NULL) {
      throw new \Exception("Event '${title}' does not exist.");
    }

    $response = $this->testBridge->command(
      'add-event-manager',
      uid: $current_user->uid,
      event_id: $event_id,
    );

    assert($response['status'] !== 'error', $response['error']);
  }

  /**
   * Create event enrollments if it doesn't matter who is enrolled.
   *
   * @Given there are :count event enrollments for the :title event
   * @Given there is :count event enrollment for the :title event
   */
  public function thereAreEventEnrollmentsForEvent(int $count, string $title) : void {
    assert(abs($count) === $count, "The :count may not be negative (got $count).");

    $event_id = $this->getEventIdFromTitle($title);
    if ($event_id === NULL) {
      throw new \Exception("Event '${title}' does not exist.");
    }

    $users = [];
    for ($i = 0; $i < $count; $i++) {
      // Create a new random user to add as enrollee.
      $user = [
        'name' => $this->drupalContext->getRandom()->name(8),
        'pass' => $this->drupalContext->getRandom()->name(16),
        'role' => "authenticated",
      ];
      $user['mail'] = "{$user['name']}@example.com";
      $users[] = $user;
    }

    $response = $this->testBridge->command(
      "create-users",
      users: $users
    );
    assert(isset($response['errors']) && $response['errors'] === [], "Could not create all required users: \n - " . implode("\n - ", $response['errors']));

    $enrollments = [];
    foreach ($response['created'] as $user_id) {
      // Event enrollments should get cleaned up when users are deleted.
      $enrollments[] = [
        'user_id' => $user_id,
        'field_event' => $event_id,
        'field_enrollment_status' => '1',
        'field_account' => $user_id,
      ];
    }

    $response = $this->testBridge->command(
      "create-event-enrollments",
      event_enrollments: $enrollments,
    );
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

  /**
   * Add anonymous enrollees to event.
   *
   * Adds anonymous enrollees to a specific event
   * | event    | name | lastname | email               |
   * | My event | Jane | Doe      | example@example.com |
   * | ...      | ...  | ...      | ...                 |
   *
   * @Given anonymous event enrollees:
   */
  public function createAnonymousEventEnrollees(TableNode $eventAnonymousEnrolleesTable) {
    $enrollments = [];
    foreach ($eventAnonymousEnrolleesTable->getHash() as $eventEnrolleesHash) {
      $event_title = $eventEnrolleesHash['event'];
      $event_id = $this->getEventIdFromTitle($event_title);
      if ($event_id === NULL) {
        throw new \Exception("Event '${event_title}' does not exist.");
      }

      $token = Crypt::randomBytesBase64();

      // @todo Remove the indirection here.
      $values = [];
      $values['user_id'] = '0';
      $values['field_account'] = '0';
      $values['field_email'] = $eventEnrolleesHash['email'];
      $values['field_enrollment_status'] = '1';
      $values['field_event'] = $event_id;
      $values['field_first_name'] = $eventEnrolleesHash['name'];
      $values['field_last_name'] = $eventEnrolleesHash['lastname'];
      $values['field_token'] = $token;

      $enrollments[] = $values;
    }

    $response = $this->testBridge->command(
      "create-event-enrollments",
      event_enrollments: $enrollments,
    );
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

  /**
   * Enable the calendar button with a given calendar.
   *
   * @Given add to calendar is enabled for :calendar
   */
  public function enableCalendarOption(string $calendar) {
    $response = $this->testBridge->command(
      'enable-event-add-to-calendar',
      calendar: $calendar
    );
    assert(isset($response['error']) && $response['error'] === [], "Could not enable add to calendar: {$response['error']}");
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
    $response = $this->testBridge->command(
      "event-id-from-title",
      title: $event_title
    );
    if (!isset($response['id'])) {
      throw new \RuntimeException("Missing 'id' in response from test bridge: " . json_encode($response));
    }
    return $response['id'];
  }

}
