<?php

namespace Drupal\social_like;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\User;
use Drupal\user\UserInterface;
use Drupal\votingapi\VoteInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles hook invocations for EDA related operations of the like entity.
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
        if ($parsed_url !== FALSE && isset($parsed_url['path'])) {
          // Validate that the referrer is from the same host to prevent
          // external URLs.
          $host = $request->getHost();
          $ref_host = parse_url($referrer, PHP_URL_HOST);
          if ($ref_host === NULL || $ref_host === $host) {
            $this->source = $parsed_url['path'];
          }
          else {
            // External referrer, fall back to current request path.
            $this->source = $request->getPathInfo() ?: '/';
          }
        }
        else {
          // If parsing failed, fall back to current request path.
          $this->source = $request->getPathInfo() ?: '/';
        }
      }
      else {
        // Fallback to current request path.
        $this->source = $request->getPathInfo() ?: '/';
      }
    }
    else {
      $this->source = '/';
    }

    // Ensure source is never empty - CloudEvents requires a non-empty source.
    if (empty($this->source)) {
      $this->source = '/';
    }

    // Set route name.
    $this->routeName = $this->routeMatch->getRouteName() ?: '';

    // Set the community namespace.
    $this->namespace = $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';

    // Set the topic name.
    $this->topicName = "{$this->namespace}.cms.like.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Create like handler.
   */
  public function likeCreate(VoteInterface $vote): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.like.create",
      vote: $vote
    );
  }

  /**
   * Delete like handler.
   */
  public function likeDelete(VoteInterface $vote): void {
    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.like.delete",
      vote: $vote
    );
  }

  /**
   * Transforms a VoteInterface into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function fromEntity(VoteInterface $vote, string $event_type): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

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
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'like' => [
          'id' => $vote->uuid(),
          'created' => DateTime::fromTimestamp($vote->getCreatedTime())->toString(),
          'updated' => DateTime::fromTimestamp($vote->getCreatedTime())->toString(),
          'target' => $target,
          'user' => User::fromEntity($vote->getOwner()),
        ],
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
   * @param \Drupal\votingapi\VoteInterface $vote
   *   The vote object.
   */
  private function dispatch(string $topic_name, string $event_type, VoteInterface $vote): void {
    // Skip if required modules are not enabled.
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

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
