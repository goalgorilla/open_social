<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\CurrentGroupProvider;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Unit tests for CurrentGroupProvider.
 *
 * @group social_group
 *
 * @coversDefaultClass \Drupal\social_group\CurrentGroupProvider
 */
final class CurrentGroupProviderTest extends UnitTestCase {

  /**
   * Route match mock.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private RouteMatchInterface&MockObject $routeMatch;

  /**
   * Entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Group storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupStorage;

  /**
   * Social group helper service mock.
   *
   * @var \Drupal\social_group\SocialGroupHelperService&\PHPUnit\Framework\MockObject\MockObject
   */
  private SocialGroupHelperService&MockObject $helperService;

  /**
   * Request stack mock.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack&\PHPUnit\Framework\MockObject\MockObject
   */
  private RequestStack&MockObject $requestStack;

  /**
   * Router mock.
   *
   * @var \Symfony\Component\Routing\RouterInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private RouterInterface&MockObject $router;

  /**
   * Route parameter values keyed by name (e.g. 'group', 'node', 'post').
   *
   * @var array
   */
  private array $routeParameters = [];

  /**
   * Current route name.
   *
   * @var string|null
   */
  private ?string $routeName = NULL;

  /**
   * Current request (for RequestStack::getCurrentRequest()).
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private ?Request $currentRequest = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeParameters = [];
    $this->routeName = NULL;
    $this->currentRequest = NULL;

    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getParameter')->willReturnCallback(
      function (string $name) {
        return $this->routeParameters[$name] ?? NULL;
      }
    );
    $this->routeMatch->method('getRouteName')->willReturnCallback(
      function () {
        return $this->routeName;
      }
    );

    $this->groupStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('group')->willReturn($this->groupStorage);

    $this->helperService = $this->createMock(SocialGroupHelperService::class);

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturnCallback(
      function () {
        return $this->currentRequest;
      }
    );

    $this->router = $this->createMock(RouterInterface::class);
  }

  /**
   * Creates the provider with current mocks.
   */
  private function createProvider(): CurrentGroupProvider {
    return new CurrentGroupProvider(
      $this->routeMatch,
      $this->entityTypeManager,
      $this->helperService,
      $this->requestStack,
      $this->router,
    );
  }

  /**
   * Tests that NULL is returned when no group is in route, entity, or AJAX.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupReturnsNullWhenNoContext(): void {
    $provider = $this->createProvider();
    $this->assertNull($provider->getCurrentGroup());
  }

  /**
   * Tests that group from route parameter (entity) is returned.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromRouteParameterAsEntity(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->routeParameters['group'] = $group;

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup());
  }

  /**
   * Tests that group from route parameter (numeric id) is loaded and returned.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromRouteParameterAsNumericId(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->routeParameters['group'] = 42;
    $this->groupStorage->method('load')->with(42)->willReturn($group);

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup());
  }

  /**
   * Tests that group is resolved from passed entity via helper service.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromPassedEntity(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');
    $entity->method('id')->willReturn(10);

    $group = $this->createMock(GroupInterface::class);
    $this->helperService->method('getGroupFromEntity')
      ->with(['target_type' => 'node', 'target_id' => 10])
      ->willReturn(5);
    $this->groupStorage->method('load')->with(5)->willReturn($group);

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup($entity));
  }

  /**
   * Tests group from route entity param when route name is entity.*.
   *
   * Uses entity.post.canonical so getParameter('node') stays NULL and
   * getParameter('post') returns the content entity.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromRouteEntityParameter(): void {
    $content_entity = $this->createMock(EntityInterface::class);
    $content_entity->method('getEntityTypeId')->willReturn('post');
    $content_entity->method('id')->willReturn(7);

    $this->routeParameters['group'] = NULL;
    $this->routeParameters['node'] = NULL;
    $this->routeParameters['post'] = $content_entity;
    $this->routeName = 'entity.post.canonical';

    $group = $this->createMock(GroupInterface::class);
    $this->helperService->method('getGroupFromEntity')
      ->with(['target_type' => 'post', 'target_id' => 7])
      ->willReturn(3);
    $this->groupStorage->method('load')->with(3)->willReturn($group);

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup());
  }

  /**
   * Tests that group is resolved from AJAX view_path when request is XML.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromAjaxViewPath(): void {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->query->set('view_path', '/group/1');
    $this->currentRequest = $request;

    $group = $this->createMock(GroupInterface::class);
    $this->router->method('match')->with('/group/1')->willReturn(['group' => 1]);
    $this->groupStorage->method('load')->with(1)->willReturn($group);

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup());
  }

  /**
   * Tests that route takes precedence over entity and AJAX.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupRouteTakesPrecedence(): void {
    $route_group = $this->createMock(GroupInterface::class);
    $this->routeParameters['group'] = $route_group;

    $entity = $this->createMock(EntityInterface::class);

    $this->helperService->expects($this->never())->method('getGroupFromEntity');
    $provider = $this->createProvider();
    $this->assertSame($route_group, $provider->getCurrentGroup($entity));
  }

  /**
   * Tests that result is cached per call (same key returns same result).
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupCachesResult(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->routeParameters['group'] = $group;

    $provider = $this->createProvider();
    $first = $provider->getCurrentGroup();
    $second = $provider->getCurrentGroup();
    $this->assertSame($first, $second);
    $this->assertSame($group, $second);
  }

  /**
   * Tests that resetCache clears the cache.
   *
   * @covers ::resetCache
   * @covers ::getCurrentGroup
   */
  public function testResetCacheClearsCache(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->routeParameters['group'] = $group;

    $provider = $this->createProvider();
    $this->assertSame($group, $provider->getCurrentGroup());

    $this->routeParameters = [];
    $this->routeName = NULL;
    $provider->resetCache();
    $this->assertNull($provider->getCurrentGroup());
  }

  /**
   * Tests that different entities use different cache keys (by entity id).
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupCacheKeyByEntityId(): void {
    $entity_a = $this->createMock(EntityInterface::class);
    $entity_a->method('id')->willReturn(1);
    $entity_a->method('getEntityTypeId')->willReturn('node');
    $entity_b = $this->createMock(EntityInterface::class);
    $entity_b->method('id')->willReturn(2);
    $entity_b->method('getEntityTypeId')->willReturn('node');

    $group_a = $this->createMock(GroupInterface::class);
    $group_b = $this->createMock(GroupInterface::class);
    $this->helperService->method('getGroupFromEntity')
      ->willReturnCallback(function (array $ref): ?int {
        if (($ref['target_type'] ?? '') === 'node' && (int) ($ref['target_id'] ?? 0) === 1) {
          return 10;
        }
        if (($ref['target_type'] ?? '') === 'node' && (int) ($ref['target_id'] ?? 0) === 2) {
          return 20;
        }
        return NULL;
      });
    $this->groupStorage->method('load')
      ->willReturnCallback(function (int $id) use ($group_a, $group_b): ?object {
        return match ($id) {
          10 => $group_a,
          20 => $group_b,
          default => NULL,
        };
      });

    $provider = $this->createProvider();
    $this->assertSame($group_a, $provider->getCurrentGroup($entity_a));
    $this->assertSame($group_b, $provider->getCurrentGroup($entity_b));
  }

  /**
   * Tests that same id across different entity types does not share cache.
   *
   * Cache key must include entity type so e.g. node:5 and user:5 do not
   * collide (would otherwise return the wrong group for the second call).
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupCacheKeyIncludesEntityType(): void {
    $node = $this->createMock(EntityInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('id')->willReturn(5);
    $user = $this->createMock(EntityInterface::class);
    $user->method('getEntityTypeId')->willReturn('user');
    $user->method('id')->willReturn(5);

    $group_for_node = $this->createMock(GroupInterface::class);
    $group_for_user = $this->createMock(GroupInterface::class);
    $this->helperService->method('getGroupFromEntity')
      ->willReturnCallback(function (array $ref): ?int {
        if (($ref['target_type'] ?? '') === 'node' && (int) ($ref['target_id'] ?? 0) === 5) {
          return 100;
        }
        if (($ref['target_type'] ?? '') === 'user' && (int) ($ref['target_id'] ?? 0) === 5) {
          return 200;
        }
        return NULL;
      });
    $this->groupStorage->method('load')
      ->willReturnCallback(function (int $id) use ($group_for_node, $group_for_user): ?object {
        return match ($id) {
          100 => $group_for_node,
          200 => $group_for_user,
          default => NULL,
        };
      });

    $provider = $this->createProvider();
    $this->assertSame($group_for_node, $provider->getCurrentGroup($node));
    $this->assertSame($group_for_user, $provider->getCurrentGroup($user));
  }

  /**
   * Tests AJAX view_path router exception returns NULL.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromAjaxViewPathUnmatchedReturnsNull(): void {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->query->set('view_path', '/invalid-path');
    $this->currentRequest = $request;
    $this->router->method('match')->with('/invalid-path')->willThrowException(new ResourceNotFoundException());

    $provider = $this->createProvider();
    $this->assertNull($provider->getCurrentGroup());
  }

  /**
   * Tests AJAX view_path MethodNotAllowedException returns NULL.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupFromAjaxViewPathMethodNotAllowedReturnsNull(): void {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->query->set('view_path', '/group/1');
    $this->currentRequest = $request;
    $this->router->method('match')->with('/group/1')->willThrowException(new MethodNotAllowedException([]));

    $provider = $this->createProvider();
    $this->assertNull($provider->getCurrentGroup());
  }

  /**
   * Tests non-AJAX request ignores view_path.
   *
   * @covers ::getCurrentGroup
   */
  public function testGetCurrentGroupIgnoresViewPathWhenNotAjax(): void {
    $request = new Request();
    $request->query->set('view_path', '/group/1');
    $this->currentRequest = $request;
    $this->router->expects($this->never())->method('match');

    $provider = $this->createProvider();
    $this->assertNull($provider->getCurrentGroup());
  }

}
