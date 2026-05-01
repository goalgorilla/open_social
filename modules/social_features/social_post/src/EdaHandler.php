<?php

namespace Drupal\social_post;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_eda\UuidNamespace;
use Drupal\social_post\Entity\PostInterface;
use Drupal\social_post\Event\PostEntityData;
use Drupal\social_post\Types\PostContentVisibility;
use Drupal\social_post\Types\Stream;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the post entity.
 */
final class EdaHandler {

  /**
   * Constructs the EdaHandler.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ?DispatcherInterface $dispatcher = NULL,
  ) {}

  /**
   * Returns the configured EDA namespace prefix.
   */
  private function namespace(): string {
    return $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';
  }

  /**
   * Returns the Kafka topic name for post events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.post.v1";
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
   * Create post handler.
   */
  public function postCreate(PostInterface $post): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.post.create",
      post: $post
    );
  }

  /**
   * Publish post handler.
   */
  public function postPublish(PostInterface $post): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.post.publish",
      post: $post
    );
  }

  /**
   * Unpublish post handler.
   */
  public function postUnpublish(PostInterface $post): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.post.unpublish",
      post: $post
    );
  }

  /**
   * Update post handler.
   */
  public function postUpdate(PostInterface $post): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.post.update",
      post: $post
    );
  }

  /**
   * Delete post handler.
   */
  public function postDelete(PostInterface $post): void {
    $this->dispatch(
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
      source: $this->source(),
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
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $event_type, PostInterface $post, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

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
