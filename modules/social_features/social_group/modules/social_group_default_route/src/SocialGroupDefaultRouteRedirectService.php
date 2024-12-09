<?php

namespace Drupal\social_group_default_route;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\SocialGroupInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SocialGroupDefaultRouteRedirectService.
 */
class SocialGroupDefaultRouteRedirectService {

  use StringTranslationTrait;

  /**
   * Default route for group non-members.
   */
  const GROUP_ABOUT_ROUTE = 'view.group_information.page_group_about';

  /**
   * Default route for group members.
   */
  const GROUP_STREAM_ROUTE = 'social_group.stream';

  /**
   * The route name of the group default page is provided by the current module.
   */
  const ALTERNATIVE_ROUTE = 'social_group_default.group_home';

  /**
   * The route name of the default page of any group.
   */
  const DEFAULT_GROUP_ROUTE = 'entity.group.canonical';

  /**
   * SocialGroupDefaultRedirectService constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\social_group_default_route\GroupLandingTabManager $landingTabManager
   *   The landing tab manager.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
    protected ModuleHandlerInterface $moduleHandler,
    protected GroupLandingTabManager $landingTabManager,
  ) {
  }

  /**
   * Do redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent|\Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event object.
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group object.
   */
  public function doRedirect(ExceptionEvent|RequestEvent $event, SocialGroupInterface $group): void {
    $current_route = $this->routeMatch->getRouteName();
    // Get default route for current user.
    $default_route = $group->hasMember($this->currentUser) ?
      $this->getDefaultMemberRoute($group) :
      $this->getDefaultNonMemberRoute($group);

    // Determine the URL we want to redirect to.
    $url = Url::fromRoute($default_route, ['group' => $group->id()]);

    // If it's not set, set to canonical, or the current user has no access.
    if ($default_route === $current_route || $url->access($this->currentUser) === FALSE) {
      // This basically means that the normal flow remains intact.
      return;
    }

    // Redirect.
    $event->setResponse(new RedirectResponse($url->toString()));
  }

  /**
   * Get current group.
   *
   * @return ?\Drupal\social_group\SocialGroupInterface
   *   The group object or NULL.
   */
  public function getGroup(): ?SocialGroupInterface {
    // Fetch the group parameter and check if's an actual group.
    $group = $this->routeMatch->getParameter('group');
    // On some routes group param could be string.
    if (is_string($group)) {
      $group = Group::load($group);
    }

    if (!$group instanceof SocialGroupInterface) {
      return NULL;
    }

    return $group;
  }

  /**
   * Get default route for non-members.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $available_routes
   *   The available route.
   *
   * @return string
   *   The default route.
   */
  public function getDefaultNonMemberRoute(GroupInterface $group, array $available_routes = []): string {
    $group_routes = $this->getGroupDefaultRoutes($group);

    if ($group->get('default_route_an')->isEmpty() ||
      (!empty($available_routes) && !isset($available_routes[$group->get('default_route_an')->getString()]))
    ) {
      return $group_routes['non-member'] ?? self::GROUP_ABOUT_ROUTE;
    }
    else {
      return $group->get('default_route_an')->getString();
    }
  }

  /**
   * Get default route for members.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $available_routes
   *   The available route.
   *
   * @return string
   *   The default route.
   */
  public function getDefaultMemberRoute(GroupInterface $group, array $available_routes = []): string {
    $group_routes = $this->getGroupDefaultRoutes($group);

    if ($group->get('default_route')->isEmpty() ||
      (!empty($available_routes) && !isset($available_routes[$group->get('default_route')->getString()]))
    ) {
      return $group_routes['member'] ?? self::GROUP_STREAM_ROUTE;
    }
    else {
      return $group->get('default_route')->getString();
    }
  }

  /**
   * Get allowed routes for non-member.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $field_values
   *   The field values.
   *
   * @return array
   *   The array of routes.
   */
  public function getNonMemberRoutes(GroupInterface $group, array $field_values = []): array {
    return $this->landingTabManager->getAvailableLendingTabs($group, GroupLandingTabManagerInterface::NON_MEMBER, $field_values);
  }

  /**
   * Get allowed routes for group member.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $field_values
   *   The field values.
   *
   * @return array
   *   The array of routes.
   */
  public function getMemberRoutes(GroupInterface $group, array $field_values = []): array {
    return $this->landingTabManager->getAvailableLendingTabs($group, GroupLandingTabManagerInterface::MEMBER, $field_values);
  }

  /**
   * Get group default routes.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return array
   *   The array of routes.
   */
  public function getGroupDefaultRoutes(GroupInterface $group): array {
    // Get available group default routes.
    $available_member_routes = array_keys($this->getMemberRoutes($group));
    $available_non_member_routes = array_keys($this->getNonMemberRoutes($group));
    // Get all group routes provided by other modules.
    $group_bundles = $this->moduleHandler->invokeAll('social_group_default_route_group_types');
    $this->moduleHandler->alter('social_group_default_route_group_types', $group_bundles);
    // Get the route names.
    $default_member_route = $group_bundles[$group->bundle()][GroupLandingTabManagerInterface::MEMBER] ?? '';
    $default_non_member_route = $group_bundles[$group->bundle()][GroupLandingTabManagerInterface::NON_MEMBER] ?? '';
    // Check if the default routes are available.
    $member_route = in_array($default_member_route, $available_member_routes) ? $default_member_route : '';
    $non_member_route = in_array($default_non_member_route, $available_non_member_routes) ? $default_non_member_route : '';

    $result = [];
    if ($member_route) {
      $result[GroupLandingTabManagerInterface::MEMBER] = $member_route;
    }

    if ($non_member_route) {
      $result[GroupLandingTabManagerInterface::NON_MEMBER] = $non_member_route;
    }

    return $result;
  }

  /**
   * Get supported group type.
   *
   * @return array
   *   The list of group bundle.
   */
  public function getSupportedGroupTypes(): array {
    $group_types = $this->moduleHandler->invokeAll('social_group_default_route_group_types');
    $this->moduleHandler->alter('social_group_default_route_group_types', $group_types);
    return array_keys($group_types);
  }

}
