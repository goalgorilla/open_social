<?php

namespace Drupal\social_comment;

use CloudEvents\V1\CloudEvent;
use Drupal\comment\CommentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_comment\Event\CommentEntityData;
use Drupal\social_comment\Event\Thread;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_eda\UuidNamespace;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the comment entity.
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
   * Returns the Kafka topic name for comment events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.comment.v1";
  }

  /**
   * Returns the CloudEvents source (referrer path or request path).
   */
  private function source(): string {
    // Set source from HTTP referrer or current request path.
    // This is required as otherwise the source is always the form submit URL.
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      // Try to get the referrer first (the actual page the user was on).
      $referrer = $request->headers->get('referer');
      if ($referrer) {
        // Extract just the path from the referrer URL.
        $parsed_url = parse_url($referrer);
        return $parsed_url['path'] ?? $referrer;
      }
      // Fallback to current request path.
      return $request->getPathInfo();
    }
    return '';
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
   * Create comment handler.
   */
  public function commentCreate(CommentInterface $comment): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.comment.create",
      comment: $comment
    );
  }

  /**
   * Publish comment handler.
   */
  public function commentPublish(CommentInterface $comment): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.comment.publish",
      comment: $comment
    );
  }

  /**
   * Unpublish comment handler.
   */
  public function commentUnpublish(CommentInterface $comment): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.comment.unpublish",
      comment: $comment
    );
  }

  /**
   * Update comment handler.
   */
  public function commentUpdate(CommentInterface $comment): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.comment.update",
      comment: $comment
    );
  }

  /**
   * Delete comment handler.
   */
  public function commentDelete(CommentInterface $comment): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.comment.delete",
      comment: $comment,
      op: 'delete'
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and comment.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.comment.create").
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, CommentInterface $comment): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }

    $uuid = $comment->uuid();

    // Build deterministic string using the full event type.
    // For create and delete, use only event_type, project_id and uuid.
    // For publish, unpublish, and update, include changed timestamp.
    switch ($event_type) {
      case "com.getopensocial.cms.comment.create":
      case "com.getopensocial.cms.comment.delete":
        $name = "$event_type:$project_id:$uuid";
        break;

      default:
        $changed = $comment->getChangedTime();
        $name = "$event_type:$project_id:$uuid:$changed";
        break;
    }

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a CommentInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(CommentInterface $comment, string $event_type, string $op = ''): CloudEvent {
    // Determine status.
    if ($op == 'delete') {
      $status = 'removed';
    }
    else {
      $status = $comment->isPublished() ? 'published' : 'unpublished';
    }

    // Get the commented entity (target).
    $commented_entity = $comment->getCommentedEntity();
    $target = $commented_entity ? EntityReference::fromEntity($commented_entity) : NULL;

    // Calculate thread information.
    $thread = $this->calculateThreadInfo($comment);

    return new CloudEvent(
      id: $this->generateEventId($event_type, $comment),
      source: $this->source(),
      type: $event_type,
      data: [
        'comment' => new CommentEntityData(
          id: $comment->uuid() ?? '',
          created: DateTime::fromTimestamp($comment->getCreatedTime())->toString(),
          updated: DateTime::fromTimestamp($comment->getChangedTime())->toString(),
          status: $status,
          target: $target,
          thread: $thread,
          author: User::fromEntity($comment->getOwner()),
          href: Href::fromEntity($comment),
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
   * Calculates thread information for the comment.
   */
  private function calculateThreadInfo(CommentInterface $comment): Thread {
    if ($comment->hasParentComment()) {
      $parent = $comment->getParentComment();
      if ($parent) {
        $parent_id = $parent->uuid() ?? '';

        // Find the root comment by traversing up the parent chain.
        $root_comment = $this->findRootComment($parent);
        $root_id = $root_comment->uuid() ?? '';
      }
      else {
        $parent_id = NULL;
        $root_id = $comment->uuid() ?? '';
      }

      // Calculate depth by counting parent levels.
      $depth = $this->calculateCommentDepth($comment);
    }
    else {
      // This is a top-level comment, so it's its own root.
      $root_id = $comment->uuid() ?? '';
      $parent_id = NULL;
      $depth = 0;
    }

    return new Thread(
      root_id: $root_id,
      parent_id: $parent_id,
      depth: $depth,
    );
  }

  /**
   * Finds the root comment by traversing up the parent chain.
   */
  private function findRootComment(CommentInterface $comment): CommentInterface {
    while ($comment->hasParentComment()) {
      $parent = $comment->getParentComment();
      if ($parent) {
        $comment = $parent;
      }
      else {
        break;
      }
    }
    return $comment;
  }

  /**
   * Calculates the depth of a comment in the thread.
   */
  private function calculateCommentDepth(CommentInterface $comment): int {
    $depth = 0;
    while ($comment->hasParentComment()) {
      $parent = $comment->getParentComment();
      if ($parent) {
        $comment = $parent;
        $depth++;
      }
      else {
        break;
      }
    }
    return $depth;
  }

  /**
   * Dispatches the event.
   *
   * @param string $event_type
   *   The event type.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment object.
   * @param string $op
   *   The operation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function dispatch(string $event_type, CommentInterface $comment, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

    try {
      // Build the event.
      $event = $this->fromEntity($comment, $event_type, $op);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log error but don't break the comment operation flow.
      $this->loggerFactory->get('social_comment')->error('Failed to dispatch EDA event for comment action. Topic: @topic, Event type: @event_type, Comment ID: @comment_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@comment_id' => $comment->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
