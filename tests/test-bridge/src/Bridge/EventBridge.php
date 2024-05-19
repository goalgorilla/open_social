<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\social_event\Entity\EventEnrollment;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class EventBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('module_handler'),
    );
  }

  #[Command(name: "event-id-from-title")]
  public function getEventIdFromTitle(string $title) {
    return ['id' => $this->getEntityIdFromLabel("node", "event", $title)];
  }

  /**
   * Create multiple events.
   *
   * @param array $events
   *   The event information that'll be passed to Node::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the events successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-events")]
  public function createEvents(array $events) : array {
    $created = [];
    $errors = [];
    foreach ($events as $inputId => $event) {
      try {
        $event = $this->eventCreate($event);
        $created[$inputId] = $event->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Add a user as event manager on the event.
   *
   * @param int $uid
   *   The user ID of the new event manager.
   * @param int $event_id
   *   The event ID.
   */
  #[Command(name: "add-event-manager")]
  public function addEventManager(int $uid, int $event_id) : array {
    $event = $this->entityTypeManager->getStorage('event')->load($event_id);
    if ($event === NULL) {
      return ['status' => 'error', 'message' => 'Event does not exist'];
    }
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($user === NULL) {
      return ['status' => 'error', 'message' => 'User does not exist'];
    }
    if (!$event->hasField("field_event_managers")) {
      return [
        'status' => 'error',
        'message' => "Field 'field_event_managers' not found, make sure you have the social_event_managers module enabled.",
      ];
    }

    $event->get('field_event_managers')
      ->appendItem(['target_id' => $uid]);
    $event->save();
    return ['status' => 'ok'];
  }

  /**
   * Create multiple event enrollments.
   *
   * @param array $event_enrollments
   *   The event enrollment information that'll be passed to
   *   EventEnrollment::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the enrollments successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-event-enrollments")]
  public function createEventEnrollments(array $event_enrollments) : array {
    $created = [];
    $errors = [];
    foreach ($event_enrollments as $inputId => $event_enrollment) {
      try {
        $event_enrollment = $this->eventEnrollmentCreate($event_enrollment);
        $created[$inputId] = $event_enrollment->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Enable the add to calendar button for a given calendar.
   */
  #[Command(name: 'enable-event-add-to-calendar')]
  public function enableCalendarOption(string $calendar) {
    if (!$this->moduleHandler->moduleExists('social_event_addtocal')) {
      throw new \Exception("Could not enable calendar button because the Social Event Add To Calendar module is disabled.");
    }

    $calendar = strtolower($calendar);

    $config =  $this->configFactory
      ->getEditable('social_event_addtocal.settings');
    $available_calendars = (array) $config->get('allowed_calendars');

    // Enable given calendar.
    $available_calendars[$calendar] = $calendar;

    $config
      ->set('enable_add_to_calendar', TRUE)
      ->set('allowed_calendars', $available_calendars)
      ->save();
  }

  /**
   * Create an event.
   *
   * @return \Drupal\node\Entity\Node
   *   The event values.
   */
  private function eventCreate(array $event) : Node {
    if (!isset($event['author'])) {
      throw new \Exception("You must specify an `author` when creating an event. Specify the `author` field if using `@Given events:` or use one of `@Given events with non-anonymous author:` or `@Given events authored by current user:` instead.");
    }

    $account = user_load_by_name($event['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $event['author']));
    }
    $event['uid'] = $account->id();
    unset($event['author']);

    if (isset($event['group'])) {
      $group_id = $this->getEntityIdFromLabel('group', NULL, $topic['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$event['group']}' does not exist.");
      }
      unset($event['group']);
    }

    $event['type'] = 'event';

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
   * Create an event enrollment.
   *
   * @param array $event_enrollment
   *
   * @return \Drupal\social_event\Entity\EventEnrollment
   *   The event enrollment values.
   */
  private function eventEnrollment(array $event_enrollment) : EventEnrollment {
    $this->validateEntityFields("event_enrollment", $event_enrollment);
    $event_enrollment_object = EventEnrollment::create($event_enrollment);
    $violations = $event_enrollment_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The event enrollment you tried to create is invalid: $violations");
    }
    $event_enrollment_object->save();
  }

}
