<?php

namespace Drupal\social_analytics\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_analytics\EdaHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for tracking page views.
 */
class PageViewSubscriber implements EventSubscriberInterface {

  /**
   * The EDA handler for page view tracking.
   *
   * @var \Drupal\social_analytics\EdaHandler
   */
  protected EdaHandler $edaHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a PageViewSubscriber object.
   *
   * @param \Drupal\social_analytics\EdaHandler $eda_handler
   *   The EDA handler for page view tracking.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    EdaHandler $eda_handler,
    AccountProxyInterface $current_user,
  ) {
    $this->edaHandler = $eda_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::VIEW][] = ['onView', 1];
    return $events;
  }

  /**
   * Handles the kernel view event.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The view event.
   */
  public function onView(ViewEvent $event): void {
    // Skip if not main request.
    if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
      return;
    }

    // Skip if CLI.
    if (PHP_SAPI === 'cli') {
      return;
    }

    // Skip if not authenticated.
    if (!$this->currentUser->isAuthenticated()) {
      return;
    }

    $request = $event->getRequest();

    // Skip AJAX requests.
    if ($this->isAjaxRequest($request)) {
      return;
    }

    // Track page view.
    // Static files are not tracked this way, so we don't need to check for
    // them separately.
    $this->edaHandler->trackPageView();
  }

  /**
   * Check if the request is an AJAX request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if the request is an AJAX request, FALSE otherwise.
   */
  protected function isAjaxRequest(Request $request): bool {
    // Our main check is for isXmlHttpRequest().
    if ($request->isXmlHttpRequest()) {
      return TRUE;
    }

    // Less reliable, but we do it as an additional check.
    $accept_header = $request->headers->get('accept', '');
    if ($accept_header && str_contains($accept_header, 'application/json')) {
      return TRUE;
    }

    return FALSE;
  }

}
