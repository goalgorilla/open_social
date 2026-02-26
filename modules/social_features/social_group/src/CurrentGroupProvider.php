<?php

declare(strict_types=1);

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides the current group from the request/route context.
 *
 * Logic is fully contained in this service so it can be unit tested and
 * reused. The procedural _social_group_get_current_group() delegates here
 * for backwards compatibility.
 */
final class CurrentGroupProvider implements CurrentGroupProviderInterface {

  /**
   * Request-scoped cache keyed by entity type and id, or -1 for NULL.
   *
   * Unsaved entities use a unique key to avoid collisions between different
   * entity instances.
   *
   * @var array<int|string, \Drupal\group\Entity\GroupInterface|false>
   */
  private array $cache = [];

  /**
   * Constructs the provider.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\social_group\SocialGroupHelperService $helperService
   *   The social group helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router (no access checks) for resolving view_path in AJAX context.
   */
  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly SocialGroupHelperService $helperService,
    private readonly RequestStack $requestStack,
    private readonly RouterInterface $router,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getCurrentGroup(?EntityInterface $entity = NULL): ?GroupInterface {
    $cache_key = $this->getCacheKey($entity);

    if (isset($this->cache[$cache_key])) {
      $cached = $this->cache[$cache_key];
      return $cached instanceof GroupInterface ? $cached : NULL;
    }

    $group = $this->resolveGroupFromRoute();
    if ($group !== NULL) {
      return $this->storeAndReturn($cache_key, $group);
    }

    $group = $this->resolveGroupFromEntity($entity);
    if ($group !== NULL) {
      return $this->storeAndReturn($cache_key, $group);
    }

    $group = $this->resolveGroupFromAjaxViewPath();
    return $this->storeAndReturn($cache_key, $group);
  }

  /**
   * Resolves the group from the current route parameter.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group if present on the route, NULL otherwise.
   */
  private function resolveGroupFromRoute(): ?GroupInterface {
    $group = $this->routeMatch->getParameter('group');
    if ($group === NULL) {
      return NULL;
    }
    return $this->ensureGroupEntity($group);
  }

  /**
   * Resolves the group from the given entity or from route entity parameters.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Optional entity to derive the group from.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group if derivable from entity/route, NULL otherwise.
   */
  private function resolveGroupFromEntity(?EntityInterface $entity): ?GroupInterface {
    $resolved_entity = $this->resolveEntityForGroupLookup($entity);
    if ($resolved_entity === NULL) {
      return NULL;
    }

    $ref = [
      'target_type' => $resolved_entity->getEntityTypeId(),
      'target_id' => $resolved_entity->id(),
    ];
    $gid = $this->helperService->getGroupFromEntity($ref);
    if ($gid === NULL) {
      return NULL;
    }

    $group = $this->entityTypeManager->getStorage('group')->load($gid);
    return $group instanceof GroupInterface ? $group : NULL;
  }

  /**
   * Resolves the entity to use for group lookup (given entity or route param).
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Optional entity passed to getCurrentGroup().
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity to use for group lookup, or NULL.
   */
  private function resolveEntityForGroupLookup(?EntityInterface $entity): ?EntityInterface {
    if ($entity !== NULL) {
      return $entity;
    }
    if ($this->routeMatch->getParameter('node') !== NULL) {
      return NULL;
    }
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === NULL) {
      return NULL;
    }
    if (!preg_match('/^entity\.([^.]+)/', $route_name, $matches)) {
      return NULL;
    }
    $param = $this->routeMatch->getParameter($matches[1]);
    return $param instanceof EntityInterface ? $param : NULL;
  }

  /**
   * Resolves the group from AJAX request view_path when applicable.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group from the matched view_path route, or NULL.
   */
  private function resolveGroupFromAjaxViewPath(): ?GroupInterface {
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL || !$request->isXmlHttpRequest()) {
      return NULL;
    }

    $view_path = $request->get('view_path');
    if ($view_path === NULL || $view_path === '') {
      return NULL;
    }

    try {
      $match = $this->router->match($view_path);
      $group = $match['group'] ?? NULL;
      return $group !== NULL ? $this->ensureGroupEntity($group) : NULL;
    }
    catch (ResourceNotFoundException | MethodNotAllowedException) {
      return NULL;
    }
  }

  /**
   * Ensures the value is a Group entity (loads by id if numeric).
   *
   * @param mixed $group
   *   Route parameter value (group entity or numeric id).
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group entity, or NULL.
   */
  private function ensureGroupEntity(mixed $group): ?GroupInterface {
    if ($group instanceof GroupInterface) {
      return $group;
    }
    if (!is_numeric($group)) {
      return NULL;
    }
    $loaded = $this->entityTypeManager->getStorage('group')->load((int) $group);
    return $loaded instanceof GroupInterface ? $loaded : NULL;
  }

  /**
   * Stores the result in cache and returns the group or NULL.
   *
   * @param int|string $cache_key
   *   Cache key.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   Resolved group or NULL.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group or NULL.
   */
  private function storeAndReturn(int|string $cache_key, ?GroupInterface $group): ?GroupInterface {
    $this->cache[$cache_key] = $group instanceof GroupInterface ? $group : FALSE;
    return $group instanceof GroupInterface ? $group : NULL;
  }

  /**
   * Returns the cache key for the given entity.
   *
   * Saved entities use "{entity_type}:{id}" so different entity types do not
   * collide. Unsaved entities use "new:{entity_type}:{object_id}".
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity, or NULL for route/context-based lookup.
   *
   * @return int|string
   *   Cache key: -1 for NULL, "{entity_type}:{id}" for saved entities,
   *   "new:{entity_type}:{object_id}" for unsaved entities.
   */
  private function getCacheKey(?EntityInterface $entity): int|string {
    if ($entity === NULL) {
      return -1;
    }
    $entity_type = $entity->getEntityTypeId();
    $id = $entity->id();
    if ($id !== NULL && $id !== '') {
      return $entity_type . ':' . $id;
    }
    return 'new:' . $entity_type . ':' . spl_object_id($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(): void {
    $this->cache = [];
  }

}
