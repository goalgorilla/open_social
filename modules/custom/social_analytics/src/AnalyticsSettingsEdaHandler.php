<?php

declare(strict_types=1);

namespace Drupal\social_analytics;

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
use Drupal\social_eda\UuidNamespace;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Dispatches platform analytics settings change events to Kafka.
 */
final class AnalyticsSettingsEdaHandler {

  private const EVENT_TYPE = 'com.getopensocial.cms.analytics.settings';

  public function __construct(
    private readonly ?DispatcherInterface $dispatcher,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly RequestStack $requestStack,
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Emits a platform analytics settings event after config persistence.
   */
  public function dispatchDefaultChange(bool $show_in_individual_metrics): void {
    if (!$this->moduleHandler->moduleExists('social_eda') || !$this->dispatcher) {
      return;
    }

    $topic_name = $this->topicName();

    try {
      $event = $this->fromDefaultChange($show_in_individual_metrics);
      $this->dispatcher->dispatch($topic_name, $event);
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('social_analytics')->error(
        'Failed to dispatch platform analytics settings event. Topic: @topic, Error: @error',
        [
          '@topic' => $topic_name,
          '@error' => $e->getMessage(),
        ],
      );
    }
  }

  /**
   * Builds the CloudEvent for a platform default change.
   */
  public function fromDefaultChange(bool $show_in_individual_metrics): CloudEvent {
    return new CloudEvent(
      id: $this->generateEventId($show_in_individual_metrics),
      source: $this->source(),
      type: self::EVENT_TYPE,
      data: [
        'settings' => [
          'showInIndividualMetrics' => $show_in_individual_metrics,
        ],
        'actor' => Actor::fromContext($this->currentUser, $this->routeMatch->getRouteName() ?: ''),
      ],
      dataContentType: 'application/json',
      time: DateTime::fromTimestamp($this->requestTime())->toImmutableDateTime(),
    );
  }

  /**
   * Returns the configured EDA namespace prefix.
   */
  private function namespace(): string {
    return $this->configFactory->get('social_eda.settings')->get('namespace') ?? 'com.getopensocial';
  }

  /**
   * Returns the Kafka topic name for analytics settings events.
   */
  private function topicName(): string {
    return "{$this->namespace()}.cms.analytics.v1";
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
   * Generates a deterministic UUIDv5 CloudEvent ID.
   */
  private function generateEventId(bool $show_in_individual_metrics): string {
    $project_id = Settings::get('project_id');
    if (empty($project_id)) {
      throw new \RuntimeException('The project_id must be configured to ensure deterministic UUIDs.');
    }

    $name = sprintf(
      '%s:%s:%d:%d',
      self::EVENT_TYPE,
      $project_id,
      (int) $show_in_individual_metrics,
      $this->requestTime(),
    );

    return Uuid::uuid5(UuidNamespace::NAMESPACE_OPENSOCIAL, $name)->toString();
  }

}
