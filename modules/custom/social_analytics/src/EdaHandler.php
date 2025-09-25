<?php

declare(strict_types=1);

namespace Drupal\social_analytics;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Application;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles EDA related operations for page view tracking.
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
   * Constructor for the Page View EDA handler.
   */
  public function __construct(
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
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

    // Set source from current request path.
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $this->source = $request->getPathInfo() ?: '/';
    }
    else {
      $this->source = '/';
    }

    // Ensure source is never empty - CloudEvents requires a non-empty source.
    if (empty($this->source)) {
      $this->source = '/';
    }

    // Set the community namespace.
    $this->namespace = $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';

    // Set the topic name.
    $this->topicName = "{$this->namespace}.cms.session.v1";

    // Set the request time.
    $this->requestTime = $this->time->getRequestTime();
  }

  /**
   * Track page view.
   */
  public function trackPageView(): void {
    // We care only about authenticated users as of now.
    if (!$this->currentUser) {
      return;
    }

    // We need a request to dispatch the event.
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return;
    }

    $this->dispatch(
      topic_name: $this->topicName,
      event_type: "{$this->namespace}.cms.page_view",
      request: $request,
    );
  }

  /**
   * Transforms a page view into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function fromPageView(Request $request, string $event_type): CloudEvent {
    // Determine actors.
    [$actor_application, $actor_user] = $this->determineActors();

    // Get entity and canonical URL if available.
    $entity = $this->getEntityFromRoute();

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $this->source,
      type: $event_type,
      data: [
        'url' => $request->getUri(),
        'target' => $entity ? [EntityReference::fromEntity($entity)] : NULL,
        'actor' => [
          'application' => $actor_application ? Application::fromId($actor_application) : NULL,
          'user' => $actor_user ? User::fromEntity($actor_user) : NULL,
        ],
      ],
      time: DateTime::fromTimestamp($this->requestTime)->toImmutableDateTime(),
    );
  }

  /**
   * Get entity from current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if found, NULL otherwise.
   */
  protected function getEntityFromRoute(): ?EntityInterface {
    $route_parameters = $this->routeMatch->getParameters();

    // Supported entity types for tracking.
    // We exclude profile entities, as we care only about the user entity.
    $entity_types = [
      'node',
      'group',
      'user',
      'post',
      'comment',
    ];

    foreach ($entity_types as $entity_type) {
      if ($route_parameters->has($entity_type)) {
        $entity = $route_parameters->get($entity_type);
        if ($entity instanceof EntityInterface) {
          return $entity;
        }
      }
    }

    return NULL;
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

    if ($this->routeMatch->getRouteName() == 'entity.ultimate_cron_job.run') {
      $application = 'cron';
    }

    return [
      $application,
      $user,
    ];
  }

  /**
   * Dispatch the event to the message broker.
   *
   * @param string $topic_name
   *   The topic name.
   * @param string $event_type
   *   The event type.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  protected function dispatch(string $topic_name, string $event_type, Request $request): void {
    if (!$this->dispatcher) {
      return;
    }

    try {
      $event = $this->fromPageView($request, $event_type);
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Exception $e) {
      // Log error but don't break the page load.
      $this->loggerFactory->get('social_analytics')->error('Failed to dispatch page view event: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
