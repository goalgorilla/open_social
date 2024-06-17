<?php

namespace Drupal\social_group_request\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Implements the group request RedirectSubscriber.
 *
 * @package Drupal\social_group_request\EventSubscriber
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * RedirectSubscriber construct.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(CurrentRouteMatch $route_match, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRoute = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['anRequestMembershipPage'];
    return $events;
  }

  /**
   * Redirect users to the group page to start the AN Membership flow.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function anRequestMembershipPage(RequestEvent $event): void {
    // First check if the current route is the AN request membership.
    $routeMatch = $this->currentRoute->getRouteName();
    if ($routeMatch !== 'social_group_request.anonymous_request_membership') {
      return;
    }

    // We need a referer, otherwise we land directly on the route which
    // isn't supported, see GroupRequestMembershipRequestAnonymousForm
    // this is opened in a Modal on the group page so we can redirect
    // after the user joins / registers.
    if (!$this->requestStack->getCurrentRequest()) {
      return;
    }
    if ($this->requestStack->getCurrentRequest()->headers->get('referer')) {
      return;
    }

    // Fetch the group parameter and check if's an actual group.
    $group = $this->currentRoute->getParameter('group');
    // Not group, then we leave.
    if (!$group instanceof Group) {
      return;
    }

    // Redirect to the entity group canonical instead, so they can click
    // join there and start the flow.
    $event->setResponse(new RedirectResponse(Url::fromRoute('entity.group.canonical', ['group' => $group->id()])->toString()));
  }

}
