<?php

namespace Drupal\social_post;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\UuidNamespace;
use Drupal\social_eda\Types\Actor;
use Drupal\social_post\Types\PostContentVisibility;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_post\Entity\PostInterface;
use Drupal\social_post\Event\PostEntityData;
use Drupal\social_post\Types\Stream;
use Drupal\user\UserInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the post entity.
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
    $this->topicName = "{$this->namespace}.cms.post.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create post handler.
   */
  public function postCreate(PostInterface $post): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.post.create",
      post: $post
    );
  }

  /**
   * Publish post handler.
   */
  public function postPublish(PostInterface $post): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.post.publish",
      post: $post
    );
  }

  /**
   * Unpublish post handler.
   */
  public function postUnpublish(PostInterface $post): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.post.unpublish",
      post: $post
    );
  }

  /**
   * Update post handler.
   */
  public function postUpdate(PostInterface $post): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.post.update",
      post: $post
    );
  }

  /**
   * Delete post handler.
   */
  public function postDelete(PostInterface $post): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.post.delete",
      post: $post,
      op: 'delete'
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and post.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.post.create").
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, PostInterface $post): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $post->uuid();

    // Build deterministic string using the full event type.
    // For create and delete, use only event_type, project_id and uuid.
    // For publish, unpublish, and update, include changed timestamp.
    switch ($event_type) {
      case "com.getopensocial.cms.post.create":
      case "com.getopensocial.cms.post.delete":
        $name = "$event_type:$project_id:$uuid";
        break;

      default:
        $changed = $post->getChangedTime();
        $name = "$event_type:$project_id:$uuid:$changed";
        break;
    }

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a PostInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(PostInterface $post, string $event_type, string $op = ''): CloudEvent {
    // Determine status.
    if ($op == 'delete') {
      $status = 'removed';
    }
    else {
      $status = $post->get('status')->value ? 'published' : 'unpublished';
    }

    return new CloudEvent(
      id: $this->generateEventId($event_type, $post),
      source: $this->source,
      type: $event_type,
      data: [
        'post' => new PostEntityData(
          id: $post->uuid() ?? '',
          created: DateTime::fromTimestamp($post->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($post->getChangedTime())->toString(),
          status: $status,
          visibility: PostContentVisibility::fromPost($post),
          stream: Stream::fromPost($post),
          author: User::fromEntity($post->get('user_id')->entity),
          href: Href::fromEntity($post),
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
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $topic_name, string $event_type, PostInterface $post, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    try {
      // Build the event.
      $event = $this->fromEntity($post, $event_type, $op);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log error but don't break the post operation flow.
      $this->loggerFactory->get('social_post')->error('Failed to dispatch EDA event for post action. Topic: @topic, Event type: @event_type, Post ID: @post_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@post_id' => $post->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
