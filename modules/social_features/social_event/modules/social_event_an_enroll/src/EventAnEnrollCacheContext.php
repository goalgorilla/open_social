<?php

namespace Drupal\social_event_an_enroll;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a class for a anonymous event enrollment route context.
 */
class EventAnEnrollCacheContext implements CacheContextInterface {

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PreviewLinkCacheContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match.
   */
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return 'Is AN enrollment route';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return ($route = $this->routeMatch->getRouteObject()) && $route->getOption('_event_an_enroll_route') ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return (new CacheableMetadata())->addCacheTags(['routes']);
  }

}
