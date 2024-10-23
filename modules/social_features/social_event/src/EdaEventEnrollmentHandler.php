<?php

namespace Drupal\social_event;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Uuid\UuidInterface;
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
use Drupal\social_eda_dispatcher\Dispatcher as SocialEdaDispatcher;
use Drupal\social_event\Event\EventEntityData;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
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
   * {@inheritDoc}
   */
  public function __construct(
    private readonly ?SocialEdaDispatcher $dispatcher,
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
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
  }

  /**
   * Unpublish event handler.
   */
  public function eventEnrollmentCreate(EventEnrollmentInterface $event_enrollment): void {
    $event_type = 'com.getopensocial.event_enrollment.create';
    $topic_name = 'com.getopensocial.event_enrollment.create';
    $this->dispatch($topic_name, $event_type, $event_enrollment);
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
      EventEnrollmentInterface::REQUEST_PENDING => 'pending',
      EventEnrollmentInterface::REQUEST_APPROVED => 'approved',
      EventEnrollmentInterface::INVITE_INVITED => 'invited',
      EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED => 'joined',
    ];

    $enrollment_status_value = (string) $event_enrollment
      ->get('field_request_or_invite_status')
      ->value;

    if (!$enrollment_status_value) {
      $enrollment_status_value = 1;
    }

    // Get event.
    $event = $event_enrollment->getEvent();
    assert($event instanceof NodeInterface);

    // Get enrollee data.
    $enrollee = $event_enrollment->getAccountEntity();
    $enrollee_data = [];
    if ($enrollee && $enrollee->id() != 0) {
      $enrollee_data = [
        'id' => (string) $enrollee->uuid(),
        'displayName' => (string) $enrollee->getDisplayName(),
        'email' => "",
        'href' => Href::fromEntity($enrollee),
      ];
    }
    else {
      $first_name = $event_enrollment->get('field_first_name')->value;
      $last_name = $event_enrollment->get('field_last_name')->value;
      $email = $event_enrollment->get('field_email')->value;
      $enrollee_data = [
        'id' => NULL,
        'displayName' => $first_name . " " . $last_name,
        'email' => $email,
        'href' => NULL,
      ];
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
          'status' => $enrollment_status[$enrollment_status_value],
          'event' => new EventEntityData(
            id: $event->get('uuid')->value,
            created: DateTime::fromTimestamp($event->getCreatedTime())->toString(),
            updated: DateTime::fromTimestamp($event->getChangedTime())->toString(),
            status: $event->get('status')->value ? 'published' : 'unpublished',
            label: (string) $event->label(),
            visibility: ContentVisibility::fromEntity($event),
            group: !$event->get('groups')->isEmpty() ? Entity::fromEntity($event->get('groups')->getEntity()) : NULL,
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
            type: $event->hasField('field_event_type') && !$event->get('field_event_type')->isEmpty() ? $event->get('field_event_type')->getEntity()->label() : NULL,
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
      time: DateTime::fromTimestamp($event_enrollment->getCreatedTime())->toImmutableDateTime(),
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

    switch ($this->routeName) {
      case 'entity.node.edit_form':
      case 'system.admin_content':
        $user = $this->currentUser;
        break;

      case 'entity.ultimate_cron_job.run':
        $application = 'cron';
        break;
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

    // Build the event.
    $event = $this->fromEntity($event_enrollment, $event_type);

    // Dispatch to message broker.
    $this->dispatcher->dispatch($topic_name, $event);
  }

}
