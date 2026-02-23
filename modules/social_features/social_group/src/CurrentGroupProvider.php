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
   * Request-scoped cache keyed by entity id (or -1 for NULL).
   *
   * @var array<int, \Drupal\group\Entity\GroupInterface|false>
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
    $entity_id = $entity === NULL ? -1 : (int) $entity->id();

    if (isset($this->cache[$entity_id])) {
      $cached = $this->cache[$entity_id];
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
    $this->cache[$entity_id] = $cache_value;

    return $group instanceof GroupInterface ? $group : NULL;
  }

}
