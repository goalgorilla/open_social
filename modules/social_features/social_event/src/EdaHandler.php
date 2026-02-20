<?php

namespace Drupal\social_event;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Plugin\BackfillActorAwareInterface;
use Drupal\social_eda\Traits\SetActorTrait;
use Drupal\social_eda\UuidNamespace;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\ContentVisibility;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_event\Event\EventEntityData;
use Drupal\user\UserInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the event entity.
 */
final class EdaHandler implements BackfillActorAwareInterface {

  use SetActorTrait;

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
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ?DispatcherInterface $dispatcher = NULL,
  ) {
    // Initialize $this->currentUser (from SetActorTrait) with
    // the authenticated user entity. This can be overridden via setActor().
    $account_id = $this->account->id();
    if ($account_id > 0) {
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
    $this->topicName = "{$this->namespace}.cms.event.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create event handler.
   */
  public function eventCreate(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.event.create",
      node: $node
    );
  }

  /**
   * Delete event handler.
   */
  public function eventDelete(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.event.delete",
      node: $node,
      op: 'delete');
  }

  /**
   * Publish event handler.
   */
  public function eventPublish(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.event.publish",
      node: $node
    );
  }

  /**
   * Unpublish event handler.
   */
  public function eventUnpublish(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.event.unpublish",
      node: $node
    );
  }

  /**
   * Update event handler.
   */
  public function eventUpdate(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.event.update",
      node: $node
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and node.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.event.create").
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, NodeInterface $node): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $node->uuid();

    // Build deterministic string using the full event type.
    // For create and delete, use only event_type, project_id and uuid.
    // For publish, unpublish, and update, include revision ID.
    switch ($event_type) {
      case "com.getopensocial.cms.event.create":
      case "com.getopensocial.cms.event.delete":
        $name = "$event_type:$project_id:$uuid";
        break;

      default:
        $vid = $node->getRevisionId();
        $name = "$event_type:$project_id:$uuid:$vid";
        break;
    }

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a NodeInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(NodeInterface $node, string $event_type, string $op = ''): CloudEvent {
    // Map enrollment method field values (1, 2, 3) to method strings.
    // Default to 'open' for empty/legacy data.
    $enrollment_method_map = [
      EventEnrollmentInterface::ENROLL_METHOD_JOIN => 'open',
      EventEnrollmentInterface::ENROLL_METHOD_REQUEST => 'request',
      EventEnrollmentInterface::ENROLL_METHOD_INVITE => 'invite',
    ];

    // Determine status.
    if ($op == 'delete') {
      $status = 'removed';
    }
    else {
      $status = $node->get('status')->value ? 'published' : 'unpublished';
    }

    // Resolve event type label (first referenced term, if any).
    $type_label = NULL;
    if ($node->hasField('field_event_type') && !$node->get('field_event_type')->isEmpty()) {
      $refs = $node->get('field_event_type')->referencedEntities();
      if (!empty($refs)) {
        $type_label = reset($refs)->label();
      }
    }

    // Resolve first referenced group (if any).
    $group_entity = NULL;
    if ($node->hasField('groups') && !$node->get('groups')->isEmpty()) {
      $groups = $node->get('groups')->referencedEntities();
      if (!empty($groups)) {
        $group_entity = Entity::fromEntity(reset($groups));
      }
    }

    return new CloudEvent(
      id: $this->generateEventId($event_type, $node),
      source: $this->source,
      type: $event_type,
      data: [
        'event' => new EventEntityData(
          id: $node->uuid() ?? '',
          created: DateTime::fromTimestamp($node->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($node->getChangedTime())->toString(),
          status: $status,
          label: (string) $node->label(),
          visibility: ContentVisibility::fromEntity($node),
          group: $group_entity,
          author: User::fromEntity($node->get('uid')->entity),
          allDay: $node->get('field_event_all_day')->isEmpty()
            ? FALSE
            : (bool) $node->get('field_event_all_day')->value,
          start: $node->get('field_event_date')->value,
          end: $node->get('field_event_date_end')->value,
          timezone: date_default_timezone_get(),
          address: Address::fromFieldItem(
            item: $node->get('field_event_address')->first(),
            label: $node->get('field_event_location')->value
          ),
          enrollment: [
            'enabled' => (bool) $node->get('field_event_enroll')->value,
            'method' => $enrollment_method_map[(int) ($node->get('field_enroll_method')->value ?? EventEnrollmentInterface::ENROLL_METHOD_JOIN)] ?? $enrollment_method_map[EventEnrollmentInterface::ENROLL_METHOD_JOIN],
          ],
          href: Href::fromEntity($node),
          type: $type_label,
        ),
        'actor' => Actor::fromContext($this->currentUser, $this->routeName),
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime)->toImmutableDateTime(),
    );
  }

  /**
   * Dispatches the event.
   *
   * @param string $topic_name
   *   The topic name.
   * @param string $event_type
   *   The event type.
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $topic_name, string $event_type, NodeInterface $node, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    try {
      // Build the event.
      $event = $this->fromEntity($node, $event_type, $op);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log error but don't break the event operation flow.
      $this->loggerFactory->get('social_event')->error('Failed to dispatch EDA event for event action. Topic: @topic, Event type: @event_type, Node ID: @node_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@node_id' => $node->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
