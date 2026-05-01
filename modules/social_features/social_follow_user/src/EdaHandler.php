<?php

namespace Drupal\social_follow_user;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\flag\FlaggingInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\User;
use Drupal\social_eda\UuidNamespace;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of follow user.
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
   * Returns the Kafka topic name for follow events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.follow.v1";
  }

  /**
   * Returns the CloudEvents source (referrer path or request path).
   */
  private function source(): string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $referrer = $request->headers->get('referer');
      if ($referrer) {
        $parsed_url = parse_url($referrer);
        if ($parsed_url !== FALSE && isset($parsed_url['path'])) {
          $host = $request->getHost();
          $ref_host = parse_url($referrer, PHP_URL_HOST);
          if ($ref_host === NULL || $ref_host === $host) {
            $source = $parsed_url['path'];
          }
          else {
            $source = $request->getPathInfo() ?: '/';
          }
        }
        else {
          $source = $request->getPathInfo() ?: '/';
        }
      }
      else {
        $source = $request->getPathInfo() ?: '/';
      }
    }
    else {
      $source = '/';
    }

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
   * Create follow user handler.
   */
  public function followUserCreate(FlaggingInterface $flagging): void {
    $this->dispatch(
      event_type: "com.getopensocial.follow.user.create",
      flagging: $flagging
    );
  }

  /**
   * Delete follow user handler.
   */
  public function followUserDelete(FlaggingInterface $flagging): void {
    $this->dispatch(
      event_type: "com.getopensocial.follow.user.delete",
      flagging: $flagging
    );
  }

  /**
   * Generates a deterministic UUIDv5 CloudEvent ID based on type and flagging.
   *
   * @param string $event_type
   *   The event type (e.g., "com.getopensocial.follow.user.create").
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging object.
   *
   * @return string
   *   A UUIDv5 string.
   */
  private function generateEventId(string $event_type, FlaggingInterface $flagging): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }
    $uuid = $flagging->uuid();

    // All follow user events use the format: {event_type}:{project_id}:{uuid}.
    $name = "$event_type:$project_id:$uuid";

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

  /**
   * Transforms a FlaggingInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(FlaggingInterface $flagging, string $event_type): CloudEvent {
    // Get the target user (the user being followed).
    $target_user = NULL;
    $follower_user = $flagging->getOwner();

    $flaggable = $flagging->getFlaggable();
    if ($flaggable instanceof ProfileInterface) {
      $target_user = $flaggable->getOwner();
    }

    return new CloudEvent(
      id: $this->generateEventId($event_type, $flagging),
      source: $this->source(),
      type: $event_type,
      data: [
        'follow' => [
          'id' => $flagging->uuid(),
          'created' => DateTime::fromTimestamp($flagging->getCreatedTime())->toString(),
          'updated' => DateTime::fromTimestamp($flagging->getCreatedTime())->toString(),
          'target' => $target_user ? User::fromEntity($target_user) : NULL,
          'follower' => User::fromEntity($follower_user),
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
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging object.
   */
  private function dispatch(string $event_type, FlaggingInterface $flagging): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

    try {
      // Build the event.
      $event = $this->fromEntity($flagging, $event_type);

      // Dispatch to message broker.
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      // Log the error but don't interrupt the follow/unfollow flow.
      $logger = $this->loggerFactory->get('social_follow_user');
      $logger->error('Failed to dispatch EDA event for follow user action. Topic: @topic, Event type: @event_type, Flagging ID: @flagging_id, Error: @error', [
        '@topic' => $topic_name,
        '@event_type' => $event_type,
        '@flagging_id' => $flagging->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
