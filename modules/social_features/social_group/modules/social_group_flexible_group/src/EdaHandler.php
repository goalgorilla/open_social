<?php

namespace Drupal\social_group_flexible_group;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_group_flexible_group\Event\GroupEntityData;
use Drupal\social_group_flexible_group\Types\GroupMembershipMethod;
use Drupal\social_group_flexible_group\Types\GroupVisibility;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the event entity.
 */
final class EdaHandler {

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
    private readonly ?DispatcherInterface $dispatcher,
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $account,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
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
    $this->topicName = "{$this->namespace}.cms.group.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create event handler.
   */
  public function groupCreate(GroupInterface $group): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.group.create",
      group: $group
    );
  }

  /**
   * Publish event handler.
   */
  public function groupPublish(GroupInterface $group): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.group.publish",
      group: $group
    );
  }

  /**
   * Unpublish event handler.
   */
  public function groupUnpublish(GroupInterface $group): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.group.unpublish",
      group: $group
    );
  }

  /**
   * Update event handler.
   */
  public function groupUpdate(GroupInterface $group): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.group.update",
      group: $group
    );
  }

  /**
   * Delete event handler.
   */
  public function groupDelete(GroupInterface $group): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.group.delete",
      group: $group
    );
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
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'group' => new GroupEntityData(
          id: $group->get('uuid')->value,
          created: DateTime::fromTimestamp($group->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($group->getChangedTime())->toString(),
          status: $status,
          label: (string) $group->label(),
          visibility: GroupVisibility::fromEntity($group),
          contentVisibility: [
            'type' => $group->get('field_group_allowed_visibility')->value,
          ],
          membership: GroupMembershipMethod::fromEntity($group),
          type: $group->getGroupType()->get('uuid'),
          author: User::fromEntity($group->get('uid')->entity),
          address: Address::fromFieldItem(
            item: $group->get('field_group_address')->first(),
            label: $group->get('field_group_location')->value
          ),
          href: Href::fromEntity($group),
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
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $topic_name, string $event_type, GroupInterface $group, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

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
