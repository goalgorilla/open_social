<?php

namespace Drupal\social_comment;

use CloudEvents\V1\CloudEvent;
use Drupal\comment\CommentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_comment\Event\CommentEntityData;
use Drupal\social_comment\Event\Thread;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the comment entity.
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

    // Set source from HTTP referrer or current request path.
    // This is required as otherwise the source is always the form submit URL.
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      // Try to get the referrer first (the actual page the user was on).
      $referrer = $request->headers->get('referer');
      if ($referrer) {
        // Extract just the path from the referrer URL.
        $parsed_url = parse_url($referrer);
        $this->source = $parsed_url['path'] ?? $referrer;
      }
      else {
        // Fallback to current request path.
        $this->source = $request->getPathInfo();
      }
    }
    else {
      $this->source = '';
    }

    // Set route name.
    $this->routeName = $this->routeMatch->getRouteName() ?: '';

    // Set the community namespace.
    $this->namespace = $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';

    // Set the topic name.
    $this->topicName = "{$this->namespace}.cms.comment.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create comment handler.
   */
  public function commentCreate(CommentInterface $comment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.comment.create",
      comment: $comment
    );
  }

  /**
   * Publish comment handler.
   */
  public function commentPublish(CommentInterface $comment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.comment.publish",
      comment: $comment
    );
  }

  /**
   * Unpublish comment handler.
   */
  public function commentUnpublish(CommentInterface $comment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.comment.unpublish",
      comment: $comment
    );
  }

  /**
   * Update comment handler.
   */
  public function commentUpdate(CommentInterface $comment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.comment.update",
      comment: $comment
    );
  }

  /**
   * Delete comment handler.
   */
  public function commentDelete(CommentInterface $comment): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "com.getopensocial.cms.comment.delete",
      comment: $comment,
      op: 'delete'
    );
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
      id: $this->uuid->generate(),
      source: $this->source,
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
        'actor' => Actor::fromContext($this->currentUser, $this->routeName),
      ],
      dataContentType: 'application/json',
      dataSchema: NULL,
      subject: NULL,
      time: DateTime::fromTimestamp($this->requestTime)->toImmutableDateTime(),
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
   * @param string $topic_name
   *   The topic name.
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
  private function dispatch(string $topic_name, string $event_type, CommentInterface $comment, string $op = ''): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

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
