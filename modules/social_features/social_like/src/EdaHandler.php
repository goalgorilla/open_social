<?php

namespace Drupal\social_like;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\User;
use Drupal\social_eda\UuidNamespace;
use Drupal\votingapi\VoteInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the like entity.
 */
final class EdaHandler {

  /**
   * Constructs the EdaHandler.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
   * Returns the Kafka topic name for like events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.like.v1";
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
        if ($parsed_url !== FALSE && isset($parsed_url['path'])) {
          // Validate that the referrer is from the same host to prevent
          // external URLs.
          $host = $request->getHost();
          $ref_host = parse_url($referrer, PHP_URL_HOST);
          if ($ref_host === NULL || $ref_host === $host) {
            $source = $parsed_url['path'];
          }
          else {
            // External referrer, fall back to current request path.
            $source = $request->getPathInfo() ?: '/';
          }
        }
        else {
          // If parsing failed, fall back to current request path.
          $source = $request->getPathInfo() ?: '/';
        }
      }
      else {
        // Fallback to current request path.
        $source = $request->getPathInfo() ?: '/';
      }
    }
    else {
      $source = '/';
    }

    // Ensure source is never empty - CloudEvents requires a non-empty source.
    if ($source === '') {
      $source = '/';
    }

    return $source;
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
   * Create like handler.
   */
  public function likeCreate(VoteInterface $vote): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.like.create",
      vote: $vote
    );
  }

  /**
   * Delete like handler.
   */
  public function likeDelete(VoteInterface $vote): void {
    $this->dispatch(
      event_type: "com.getopensocial.cms.like.delete",
      vote: $vote
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and vote.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.cms.like.create").
   * @param \Drupal\votingapi\VoteInterface $vote
   *   The vote object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, VoteInterface $vote): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $vote->uuid();

    // All like events use the format: event_type:project_id:uuid.
    $name = "$event_type:$project_id:$uuid";

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a VoteInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(VoteInterface $vote, string $event_type): CloudEvent {
    // Get the voted entity (target).
    $voted_entity_type = $vote->getVotedEntityType();
    $voted_entity_id = $vote->getVotedEntityId();
    $target = NULL;

    if ($voted_entity_type && $voted_entity_id) {
      $storage = $this->entityTypeManager->getStorage($voted_entity_type);
      $voted_entity = $storage->load($voted_entity_id);
      if ($voted_entity instanceof EntityInterface) {
        $target = EntityReference::fromEntity($voted_entity);
      }
    }

    return new CloudEvent(
      id: $this->generateEventId($event_type, $vote),
      source: $this->source(),
      type: $event_type,
      data: [
        'like' => [
          'id' => $vote->uuid() ?? '',
          'created' => DateTime::fromTimestamp($vote->getCreatedTime())->toString(),
          'updated' => DateTime::fromTimestamp($vote->getCreatedTime())->toString(),
          'target' => $target,
          'user' => User::fromEntity($vote->getOwner()),
        ],
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
   * @param \Drupal\votingapi\VoteInterface $vote
   *   The vote object.
   */
  private function dispatch(string $event_type, VoteInterface $vote): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

    try {
      // Build the event.
      $event = $this->fromEntity($vote, $event_type);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log the error but don't interrupt the like/unlike flow.
      $logger = $this->loggerFactory->get('social_like');
      $logger->error('Failed to dispatch EDA event for like action. Topic: @topic, Event type: @event_type, Vote ID: @vote_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@vote_id' => $vote->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
