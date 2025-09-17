<?php

namespace Drupal\social_event;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\ContentVisibility;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_event\Event\EventEntityData;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use Drupal\social_eda\DispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles invocations for EDA related operations of event enrollment entity.
 */
final class EdaEventEnrollmentHandler {

  /**
   * The current logged-in user.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $currentUser = NULL;

  /**
   * The source.
   *
   * @var string
   */
  protected string $source;

  /**
   * The current route name.
   *
   * @var string
   */
  protected string $routeName;

  /**
   * The community namespace.
   *
   * @var string
   */
  protected string $namespace;

  /**
   * The topic name.
   *
   * @var string
   */
  protected string $topicName;

  /**
   * The request time.
   *
   * @var int
   */
  protected int $requestTime;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly ?DispatcherInterface $dispatcher = NULL,
  ) {
    // Load the full user entity if the account is authenticated.
    $account_id = $this->account->id();
    if ($account_id && $account_id !== 0) {
      $user = $this->entityTypeManager->getStorage('user')->load($account_id);
      if ($user instanceof UserInterface) {
        $this->currentUser = $user;
      }
    }

    // Set source.
    $request = $this->requestStack->getCurrentRequest();
    $this->source = $request ? $request->getPathInfo() : '';

    // Set route name.
    $this->routeName = $this->routeMatch->getRouteName() ?: '';

    // Set the community namespace.
    $this->namespace = $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';

    // Set the community namespace.
    $this->topicName = "{$this->namespace}.cms.event_enrollment.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create event enrollment.
   */
  public function eventEnrollmentCreate(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.create",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Cancels an event enrollment.
   */
  public function eventEnrollmentCancel(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.delete",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Request to join event.
   */
  public function eventRequestToJoin(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.request.create",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Request to join event cancelled.
   */
  public function eventRequestToJoinCancelled(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.request.delete",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Request to join event accepted.
   */
  public function eventRequestToJoinAccepted(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.request.accept",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Request to join event declined.
   */
  public function eventRequestToJoinDeclined(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.request.decline",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Invite to join event.
   */
  public function eventInviteToJoin(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.invite.create",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Invite to join event cancelled.
   */
  public function eventInviteToJoinCancelled(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.invite.delete",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Invite to join event accepted.
   */
  public function eventInviteToJoinAccepted(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.invite.accept",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Invite to join event declined.
   */
  public function eventInviteToJoinDeclined(EventEnrollmentInterface $event_enrollment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.event_enrollment.invite.decline",
      event_enrollment: $event_enrollment,
    );
  }

  /**
   * Transforms a EventEnrollment into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(EventEnrollmentInterface $event_enrollment, string $event_type): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

    // List enrollment methods.
    $enrollment_methods = ['open', 'request', 'invite'];

    // List enrollment statuses.
    $enrollment_status = [
      "{$this->namespace}.cms.event_enrollment.create" => 'active',
      "{$this->namespace}.cms.event_enrollment.delete" => 'removed',
      "{$this->namespace}.cms.event_enrollment.request.create" => 'request_pending',
      "{$this->namespace}.cms.event_enrollment.request.delete" => 'request_cancelled',
      "{$this->namespace}.cms.event_enrollment.request.accept" => 'active',
      "{$this->namespace}.cms.event_enrollment.request.decline" => 'request_declined',
      "{$this->namespace}.cms.event_enrollment.invite.create" => 'invite_pending',
      "{$this->namespace}.cms.event_enrollment.invite.delete" => 'invite_cancelled',
      "{$this->namespace}.cms.event_enrollment.invite.accept" => 'active',
      "{$this->namespace}.cms.event_enrollment.invite.decline" => 'invite_declined',
    ];

    // Get event.
    $event = $event_enrollment->getEvent();

    // An event enrolment should never exist without an event because there
    // would be nothing to enrol to. This indicates a data integrity constraint
    // violation.
    assert($event instanceof NodeInterface);

    // Get enrollee data.
    $enrollee = $event_enrollment->getAccountEntity();

    $enrollee_data = [];
    // Enrollee is an authenticated user.
    if ($enrollee instanceof UserInterface && !$enrollee->isAnonymous()) {
      $enrollee_data = [
        'id' => (string) $enrollee->uuid(),
        'displayName' => (string) $enrollee->getDisplayName(),
        'email' => (string) $enrollee->getEmail(),
        'href' => Href::fromEntity($enrollee),
      ];
    }
    else {
      // The user is an external user.
      $first_name = NULL;
      $last_name = NULL;
      $email = NULL;
      if ($event_enrollment->hasField('field_first_name') && !$event_enrollment->get('field_first_name')->isEmpty()) {
        $first_name = $event_enrollment->get('field_first_name')->value ?? NULL;
      }
      if ($event_enrollment->hasField('field_last_name') && !$event_enrollment->get('field_last_name')->isEmpty()) {
        $last_name = $event_enrollment->get('field_last_name')->value ?? NULL;
      }
      // If first and last name are present, use them to display the name.
      $display_name = $first_name && $last_name ? $first_name . " " . $last_name : NULL;

      if ($event_enrollment->hasField('field_email') && !$event_enrollment->get('field_email')->isEmpty()) {
        $email = $event_enrollment->get('field_email')->value ?? NULL;
      }

      // Email is always required to be present.
      if ($email) {
        $enrollee_data = [
          'id' => NULL,
          'displayName' => $display_name,
          'email' => $email,
          'href' => NULL,
        ];
      }
    }

    // Resolve event type label (first referenced term, if any).
    $type_label = NULL;
    if ($event->hasField('field_event_type') && !$event->get('field_event_type')->isEmpty()) {
      $refs = $event->get('field_event_type')->referencedEntities();
      if (!empty($refs)) {
        $type_label = reset($refs)->label();
      }
    }

    // Resolve first referenced group (if any).
    $group_entity = NULL;
    if ($event->hasField('groups') && !$event->get('groups')->isEmpty()) {
      $groups = $event->get('groups')->referencedEntities();
      if (!empty($groups)) {
        $group_entity = Entity::fromEntity(reset($groups));
      }
    }

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'eventEnrollment' => [
          'id' => $event_enrollment->get('uuid')->value,
          'created' => DateTime::fromTimestamp($event_enrollment->getCreatedTime())->toString(),
          'updated' => DateTime::fromTimestamp($event_enrollment->getChangedTime())->toString(),
          'status' => $enrollment_status[$event_type],
          'event' => new EventEntityData(
            id: $event->get('uuid')->value,
            created: DateTime::fromTimestamp($event->getCreatedTime())->toString(),
            updated: DateTime::fromTimestamp($event->getChangedTime())->toString(),
            status: $event->get('status')->value ? 'published' : 'unpublished',
            label: (string) $event->label(),
            visibility: ContentVisibility::fromEntity($event),
            group: $group_entity,
            author: User::fromEntity($event->get('uid')->entity),
            allDay: $event->get('field_event_all_day')->value,
            start: $event->get('field_event_date')->value,
            end: $event->get('field_event_date_end')->value,
            timezone: date_default_timezone_get(),
            address: Address::fromFieldItem(
              item: $event->get('field_event_address')->first(),
              label: $event->get('field_event_location')->value
            ),
            enrollment: [
              'enabled' => (bool) $event->get('field_event_enroll')->value,
              'method' => $enrollment_methods[$event->get('field_enroll_method')->value],
            ],
            href: Href::fromEntity($event),
            type: $type_label,
          ),
          'user' => $enrollee_data,
        ],
        'actor' => [
          'application' => $actor_application ? Application::fromId($actor_application) : NULL,
          'user' => $actor_user ? User::fromEntity($actor_user) : NULL,
        ],
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime)->toImmutableDateTime(),
    );
  }

  /**
   * Determines the actor (application and user) for the CloudEvent.
   *
   * @return array
   *   An array with two elements: the application and the user.
   */
  private function determineActors(): array {
    $application = NULL;
    $user = NULL;

    if ($this->currentUser instanceof UserInterface) {
      $user = $this->currentUser;
    }

    if ($this->routeName == 'entity.ultimate_cron_job.run') {
      $application = 'cron';
    }

    return [
      $application,
      $user,
    ];
  }

  /**
   * Dispatches the event.
   *
   * @param string $topic_name
   *   The topic name.
   * @param string $event_type
   *   The event type.
   * @param \Drupal\social_event\EventEnrollmentInterface $event_enrollment
   *   The event enrollment.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $topic_name, string $event_type, EventEnrollmentInterface $event_enrollment): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    // An event enrolment should always have an event associated with it.
    // The user is optional, as external users are also allowed to be invited
    // or join the event.
    if (!$event_enrollment->getEvent()) {
      return;
    }

    // Build the event.
    $event = $this->fromEntity($event_enrollment, $event_type);

    // Dispatch to message broker.
    $this->dispatcher->dispatch($topic_name, $event);
  }

}
