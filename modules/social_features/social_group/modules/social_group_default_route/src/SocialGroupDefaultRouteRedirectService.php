<?php

namespace Drupal\social_group_default_route;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\SocialGroupInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;

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
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider to load routes by name.
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   The path alias manager.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
    protected ModuleHandlerInterface $moduleHandler,
    protected GroupLandingTabManager $landingTabManager,
    protected RouteProviderInterface $routeProvider,
    protected AliasManagerInterface $pathAliasManager,
  ) {}

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

    // Different routes could have the same path.
    // Make sure we are not redirecting to the route with the same path.
    $is_path_same = $this->routeMatch->getRouteObject()?->getPath() ===
      $this->routeProvider->getRouteByName($default_route)->getPath();
    if ($is_path_same) {
      return;
    }

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
   * Redirects to group canonical with ?stream when path ends with /stream.
   *
   * When path aliases shorten group canonical from /alias/stream to /alias,
   * visiting /alias/stream results in 404. This method resolves the path
   * (e.g. /alias or /group/123) to a group and returns a redirect to the
   * group canonical URL with the "stream" query parameter so the user lands
   * on the stream tab.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request that produced the 404.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response, or NULL if the path does not indicate a group
   *   stream.
   */
  public function getRedirectResponseForStreamNotFound(Request $request): ?RedirectResponse {
    $path = trim($request->getPathInfo(), '/');
    $path = '/' . $path;

    // Handle paths ending with /stream (e.g., /group/slug/stream).
    if (str_ends_with($path, '/stream')) {
      return $this->resolveStreamRedirect($path);
    }

    // Handle paths with /stream/ in the middle
    // (e.g., /group/slug/stream/about).
    if (str_contains($path, '/stream/')) {
      return $this->resolveStreamSubpageRedirect($path);
    }

    // Handle paths ending with /home (e.g., /group/slug/home).
    if (str_ends_with($path, '/home')) {
      return $this->resolveHomeRedirect($path);
    }

    return NULL;
  }

  /**
   * Resolves a redirect for paths ending with /stream.
   *
   * @param string $path
   *   The request path (e.g., /group/slug/stream).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response, or NULL if not applicable.
   */
  protected function resolveStreamRedirect(string $path): ?RedirectResponse {
    $path_without_stream = rtrim(substr($path, 0, -strlen('/stream')), '/') ?: '/';

    $group_id = $this->resolveGroupIdFromAlias($path_without_stream);

    if ($group_id === NULL) {
      return NULL;
    }

    $url = Url::fromRoute(self::DEFAULT_GROUP_ROUTE, ['group' => $group_id], [
      'query' => ['stream' => NULL],
    ]);

    if ($url->access($this->currentUser) === FALSE) {
      return NULL;
    }

    return new RedirectResponse($url->setAbsolute(TRUE)->toString(), 301);
  }

  /**
   * Resolves a redirect for paths with /stream/ in the middle.
   *
   * E.g., /group/slug/stream/about → /group/slug/about.
   *
   * @param string $path
   *   The request path containing /stream/ (e.g., /group/slug/stream/about).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response, or NULL if not applicable.
   */
  protected function resolveStreamSubpageRedirect(string $path): ?RedirectResponse {
    // Split at the first occurrence of /stream/.
    $stream_pos = strpos($path, '/stream/');
    if ($stream_pos === FALSE) {
      return NULL;
    }

    $prefix = substr($path, 0, $stream_pos);
    $suffix = substr($path, $stream_pos + strlen('/stream'));

    $group_id = $this->resolveGroupIdFromAlias($prefix);

    if ($group_id === NULL) {
      return NULL;
    }

    // Build the corrected URL without /stream.
    $corrected_alias = $this->pathAliasManager->getAliasByPath("/group/$group_id/stream");
    $redirect_path = $corrected_alias . $suffix;

    $url = Url::fromUserInput($redirect_path);

    if ($url->access($this->currentUser) === FALSE) {
      return NULL;
    }

    return new RedirectResponse($url->setAbsolute(TRUE)->toString(), 301);
  }

  /**
   * Resolves a redirect for paths ending with /home.
   *
   * @param string $path
   *   The request path (e.g., /group/slug/home).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response, or NULL if not applicable.
   */
  protected function resolveHomeRedirect(string $path): ?RedirectResponse {
    $path_without_home = rtrim(substr($path, 0, -strlen('/home')), '/') ?: '/';

    $group_id = $this->resolveGroupIdFromAlias($path_without_home);

    if ($group_id === NULL) {
      return NULL;
    }

    $url = Url::fromRoute(self::DEFAULT_GROUP_ROUTE, ['group' => $group_id]);

    if ($url->access($this->currentUser) === FALSE) {
      return NULL;
    }

    return new RedirectResponse($url->setAbsolute(TRUE)->toString(), 301);
  }

  /**
   * Resolves a group ID from an alias or system path.
   *
   * @param string $path
   *   The path to resolve (e.g., /group/slug or /group/123).
   *
   * @return int|null
   *   The group ID, or NULL if the path does not resolve to a group.
   */
  protected function resolveGroupIdFromAlias(string $path): ?int {
    // Try alias resolution first so that numeric slugs (e.g. /group/456
    // aliasing /group/123/stream) are resolved correctly.
    $system_path = $this->pathAliasManager->getPathByAlias($path);
    if ($system_path && $system_path !== $path && preg_match('#^/group/(\d+)(?:/stream)?$#', $system_path, $m)) {
      return (int) $m[1];
    }

    // Fall back to matching the original path as a system path.
    if (preg_match('#^/group/(\d+)$#', $path, $m)) {
      return (int) $m[1];
    }

    return NULL;
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
