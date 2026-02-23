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
   * Request-scoped cache keyed by entity id, "new:{object_id}", or -1 for NULL.
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

    $group = $this->routeMatch->getParameter('group');

    if (is_numeric($group)) {
      $storage = $this->entityTypeManager->getStorage('group');
      $group = $storage->load((int) $group);
    }

    if ($group === NULL) {
      $resolved_entity = $entity;
      if ($resolved_entity === NULL
        && $this->routeMatch->getParameter('node') === NULL
        && $this->routeMatch->getRouteName() !== NULL
        && preg_match('/^entity\.([^.]+)/', $this->routeMatch->getRouteName(), $matches)
      ) {
        $resolved_entity = $this->routeMatch->getParameter($matches[1]);
      }

      if ($resolved_entity instanceof EntityInterface) {
        $ref = [
          'target_type' => $resolved_entity->getEntityTypeId(),
          'target_id' => $resolved_entity->id(),
        ];
        $gid = $this->helperService->getGroupFromEntity($ref);
        if ($gid !== NULL) {
          $group = $this->entityTypeManager->getStorage('group')->load($gid);
        }
      }
    }

    if ($group === NULL && $this->requestStack->getCurrentRequest()?->isXmlHttpRequest()) {
      $view_path = $this->requestStack->getCurrentRequest()->get('view_path');
      if ($view_path !== NULL && $view_path !== '') {
        try {
          $match = $this->router->match($view_path);
          $group = $match['group'] ?? NULL;
          if (is_numeric($group)) {
            $group = $this->entityTypeManager->getStorage('group')->load((int) $group);
          }
        }
        catch (ResourceNotFoundException | MethodNotAllowedException) {
          // Ignore unmatched view paths.
        }
      }
    }

    $cache_value = $group instanceof GroupInterface ? $group : FALSE;
    $this->cache[$cache_key] = $cache_value;

    return $group instanceof GroupInterface ? $group : NULL;
  }

  /**
   * Returns the cache key for the given entity.
   *
   * Saved entities use their integer id. Unsaved entities use a unique key
   * so different instances do not share the same cache slot.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity, or NULL for route/context-based lookup.
   *
   * @return int|string
   *   Cache key: -1 for NULL, integer id for saved entities, "new:{id}" for
   *   unsaved entities.
   */
  private function getCacheKey(?EntityInterface $entity): int|string {
    if ($entity === NULL) {
      return -1;
    }
    $id = $entity->id();
    if ($id !== NULL && $id !== '') {
      return (int) $id;
    }
    return 'new:' . spl_object_id($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(): void {
    $this->cache = [];
  }

}
