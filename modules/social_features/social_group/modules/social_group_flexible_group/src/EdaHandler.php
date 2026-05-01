<?php

namespace Drupal\social_group_flexible_group;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_eda\UuidNamespace;
use Drupal\social_group_flexible_group\Event\GroupEntityData;
use Drupal\social_group_flexible_group\Types\GroupMembershipMethod;
use Drupal\social_group_flexible_group\Types\GroupVisibility;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the event entity.
 */
final class EdaHandler {

  /**
   * Constructs the EdaHandler.
   */
  public function __construct(
    private readonly ?DispatcherInterface $dispatcher,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns the configured EDA namespace prefix.
   */
  private function namespace(): string {
    return $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';
  }

  /**
   * Returns the Kafka topic name for group events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.group.v1";
  }

  /**
   * Returns the CloudEvents source (request path).
   */
  private function source(): string {
    $request = $this->requestStack->getCurrentRequest();
    return $request ? $request->getPathInfo() : '';
  }

  /**
   * Returns the current route name.
   */
  private function routeName(): string {
    return $this->routeMatch->getRouteName() ?: '';
  }

  /**
   * Returns the request time as a Unix timestamp.
   */
  private function requestTime(): int {
    return $this->time->getRequestTime();
  }

  /**
   * Create event handler.
   */
  public function groupCreate(GroupInterface $group): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.group.create",
      group: $group
    );
  }

  /**
   * Publish event handler.
   */
  public function groupPublish(GroupInterface $group): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.group.publish",
      group: $group
    );
  }

  /**
   * Unpublish event handler.
   */
  public function groupUnpublish(GroupInterface $group): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.group.unpublish",
      group: $group
    );
  }

  /**
   * Update event handler.
   */
  public function groupUpdate(GroupInterface $group): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.group.update",
      group: $group
    );
  }

  /**
   * Delete event handler.
   */
  public function groupDelete(GroupInterface $group): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.group.delete",
      group: $group
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and group.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.group.create").
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, GroupInterface $group): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $group->uuid();

    // Build deterministic string using the full event type.
    // For create and delete, use only event_type, project_id and uuid.
    // For publish, unpublish, and update, include revision ID.
    switch ($event_type) {
      case "com.getopensocial.cms.group.create":
      case "com.getopensocial.cms.group.delete":
        $name = "$event_type:$project_id:$uuid";
        break;

      default:
        $revision_id = $group->getRevisionId();
        $name = "$event_type:$project_id:$uuid:$revision_id";
        break;
    }

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a GroupInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(GroupInterface $group, string $event_type, string $op = ''): CloudEvent {
    // Determine status.
    if ($op == 'delete') {
      $status = 'removed';
    }
    else {
      $status = $group->get('status')->value ? 'published' : 'unpublished';
    }

    return new CloudEvent(
      id: $this->generateEventId($event_type, $group),
      source: $this->source(),
      type: $event_type,
      data: [
        'group' => new GroupEntityData(
          id: $group->uuid() ?? '',
          created: DateTime::fromTimestamp($group->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($group->getChangedTime())->toString(),
          status: $status,
          label: (string) $group->label(),
          visibility: GroupVisibility::fromEntity($group),
          contentVisibility: [
            'type' => $group->get('field_group_allowed_visibility')->value,
          ],
          membership: GroupMembershipMethod::fromEntity($group),
          type: $group->getGroupType()->uuid(),
          author: User::fromEntity($group->get('uid')->entity),
          address: Address::fromFieldItem(
            item: $group->get('field_group_address')->first(),
            label: $group->get('field_group_location')->value
          ),
          href: Href::fromEntity($group),
        ),
        'actor' => Actor::fromContext($this->currentUser, $this->routeName()),
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime())->toImmutableDateTime(),
    );
  }

  /**
   * Dispatches the event.
   *
   * @param string $event_type
   *   The event type.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $event_type, GroupInterface $group, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

    try {
      // Build the event.
      $event = $this->fromEntity($group, $event_type, $op);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log error but don't break the group operation flow.
      $this->loggerFactory->get('social_group_flexible_group')->error('Failed to dispatch EDA event for group action. Topic: @topic, Event type: @event_type, Group ID: @group_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@group_id' => $group->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
