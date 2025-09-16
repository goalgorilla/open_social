<?php

namespace Drupal\social_topic;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\ContentVisibility;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_topic\Event\TopicEntityData;
use Drupal\user\UserInterface;
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
      event_type: "{$this->namespace}.cms.topic.create",
      node: $node
    );
  }

  /**
   * Transforms a NodeInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(NodeInterface $node, string $event_type, string $op = ''): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

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
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'topic' => new TopicEntityData(
          id: $node->get('uuid')->value,
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

    // Build the event.
    $event = $this->fromEntity($node, $event_type, $op);

    // Dispatch to message broker.
    $this->dispatcher->dispatch($topic_name, $event);
  }

}
