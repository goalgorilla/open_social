<?php

namespace Drupal\social_group_secret\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class SocialGroupSecretSubscriber.
 *
 * @package Drupal\social_group_secret\EventSubscriber
 */
class SocialGroupSecretSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SocialGroupSecretSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // We need a higher priority than R4032Login so that it doesn't create a
    // response to login.
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function on403(GetResponseEvent $event) {
    $group = $this->routeMatch->getParameter('group');

    // Show 404 page instead of 403 page for secret groups.
    if ($group instanceof GroupInterface && $group->bundle() === 'secret_group') {
      // Change the exception to show as 404 instead of 403.
      $event->setException(new NotFoundHttpException());
    }
  }

}
