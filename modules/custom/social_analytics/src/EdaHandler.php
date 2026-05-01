<?php

declare(strict_types=1);

namespace Drupal\social_analytics;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\Actor;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_eda\Types\EntityReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles EDA related operations for page view tracking.
 */
final class EdaHandler {

  /**
   * Constructs the Page View EDA handler.
   */
  public function __construct(
    private readonly UuidInterface $uuid,
    private readonly RequestStack $requestStack,
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
   * Returns the Kafka topic name for session / page view events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.session.v1";
  }

  /**
   * Returns the CloudEvents source (request path).
   */
  private function source(): string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->getPathInfo() ?: '/';
    }

    return '/';
  }

  /**
   * Returns the request time as a Unix timestamp.
   */
  private function requestTime(): int {
    return $this->time->getRequestTime();
  }

  /**
   * Track page view.
   */
  public function trackPageView(): void {
    // We care only about authenticated users as of now.
    if (!$this->currentUser->isAuthenticated()) {
      return;
    }

    // We need a request to dispatch the event.
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return;
    }

    $this->dispatch(
      event_type: "com.getopensocial.cms.page_view",
      request: $request,
    );
  }

  /**
   * Transforms a page view into a CloudEvent.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function fromPageView(Request $request, string $event_type): CloudEvent {
    // Get entity and canonical URL if available.
    $entity = $this->getEntityFromRoute();

    return new CloudEvent(
      id: $this->uuid->generate(),
      source: $this->source(),
      type: $event_type,
      data: [
        'url' => $request->getUri(),
        'target' => $entity ? [EntityReference::fromEntity($entity)] : NULL,
        'actor' => Actor::fromContext($this->currentUser, $this->routeMatch->getRouteName() ?: ''),
      ],
      time: DateTime::fromTimestamp($this->requestTime())->toImmutableDateTime(),
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
   * Dispatch the event to the message broker.
   *
   * @param string $event_type
   *   The event type.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  protected function dispatch(string $event_type, Request $request): void {
    if (!$this->dispatcher) {
      return;
    }
    $topic_name = $this->topicName();

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
