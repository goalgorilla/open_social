<?php

namespace Drupal\social_topic;

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
use Drupal\social_eda\UuidNamespace;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\ContentVisibility;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_topic\Event\TopicEntityData;
use Drupal\user\UserInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the topic entity.
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
    // Load the full user entity if the account is authenticated.
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

    // Set the topic name.
    $this->topicName = "{$this->namespace}.cms.topic.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create topic handler.
   */
  public function topicCreate(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.topic.create",
      node: $node
    );
  }

  /**
   * Publish topic handler.
   */
  public function topicPublish(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.topic.publish",
      node: $node
    );
  }

  /**
   * Unpublish topic handler.
   */
  public function topicUnpublish(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.topic.unpublish",
      node: $node
    );
  }

  /**
   * Update topic handler.
   */
  public function topicUpdate(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.topic.update",
      node: $node
    );
  }

  /**
   * Delete topic handler.
   */
  public function topicDelete(NodeInterface $node): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.topic.delete",
      node: $node,
      op: 'delete');
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and node.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.topic.create").
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
      case "com.getopensocial.cms.topic.create":
      case "com.getopensocial.cms.topic.delete":
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
    // Determine status.
    if ($op == 'delete') {
      $status = 'removed';
    }
    else {
      $status = $node->get('status')->value ? 'published' : 'unpublished';
    }

    // Resolve topic type label (first referenced term, if any).
    $type_label = NULL;
    if ($node->hasField('field_topic_type') && !$node->get('field_topic_type')->isEmpty()) {
      $refs = $node->get('field_topic_type')->referencedEntities();
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
        'topic' => new TopicEntityData(
          id: $node->uuid() ?? '',
          created: DateTime::fromTimestamp($node->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($node->getChangedTime())->toString(),
          status: $status,
          label: (string) $node->label(),
          visibility: ContentVisibility::fromEntity($node),
          type: $type_label,
          group: $group_entity,
          author: User::fromEntity($node->get('uid')->entity),
          href: Href::fromEntity($node),
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
      // Log error but don't break the topic operation flow.
      $this->loggerFactory->get('social_topic')->error('Failed to dispatch EDA event for topic action. Topic: @topic, Event type: @event_type, Node ID: @node_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@node_id' => $node->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
