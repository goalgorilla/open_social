<?php

namespace Drupal\Tests\social_analytics\Unit;

use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use CloudEvents\V1\CloudEventInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_analytics\EdaHandler;
use Drupal\social_eda\DispatcherInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_analytics\EdaHandler
 */
class EdaHandlerTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the language_manager service.
    $languageMock = $this->createMock(LanguageInterface::class);
    $languageMock->method('getId')->willReturn('en');
    $languageManagerMock = $this->createMock(LanguageManagerInterface::class);
    $languageManagerMock->method('getCurrentLanguage')->willReturn($languageMock);

    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock);
    \Drupal::setContainer($container);
  }

  /**
   * Test trackPageView with authenticated user.
   */
  public function testTrackPageViewWithAuthenticatedUser(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->isInstanceOf(CloudEventInterface::class)
      );

    $handler = $this->createEdaHandler($dispatcher);
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with anonymous user.
   */
  public function testTrackPageViewWithAnonymousUser(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->never())
      ->method('dispatch');

    $handler = $this->createEdaHandler($dispatcher, TRUE);
    $handler->trackPageView();
  }

  /**
   * Test fromPageView with canonical URL.
   */
  public function testFromPageViewWithCanonicalUrl(): void {
    $handler = $this->createEdaHandler();
    $request = $this->createRequest();

    $event = $handler->fromPageView($request, 'com.getopensocial.cms.page_view');

    $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
    $this->assertEquals('https://example.com/test-page?param=value', $event->getData()['url']);
  }

  /**
   * Test fromPageView without canonical URL.
   */
  public function testFromPageViewWithoutCanonicalUrl(): void {
    $handler = $this->createEdaHandler();
    $request = $this->createRequest();

    $event = $handler->fromPageView($request, 'com.getopensocial.cms.page_view');

    $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
    $this->assertEquals('https://example.com/test-page?param=value', $event->getData()['url']);
  }

  /**
   * Test dispatch with exception handling.
   */
  public function testDispatchWithException(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->willThrowException(new \Exception('Test exception'));

    $handler = $this->createEdaHandler($dispatcher);

    // Should not throw exception.
    $handler->trackPageView();
  }

  /**
   * Test dispatch without dispatcher.
   */
  public function testDispatchWithoutDispatcher(): void {
    $handler = $this->createEdaHandler(NULL, FALSE, 'no_dispatcher');

    // Should not throw exception.
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with group entity.
   */
  public function testTrackPageViewWithGroup(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          $this->assertNotNull($event->getData()['target']);
          $this->assertIsArray($event->getData()['target']);
          $this->assertCount(1, $event->getData()['target']);
          $this->assertEquals('group-uuid-123', $event->getData()['target'][0]->id);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, 'group');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with user entity.
   */
  public function testTrackPageViewWithUser(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          $this->assertNotNull($event->getData()['target']);
          $this->assertIsArray($event->getData()['target']);
          $this->assertCount(1, $event->getData()['target']);
          $this->assertEquals('user-uuid-123', $event->getData()['target'][0]->id);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, 'user');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with post entity.
   */
  public function testTrackPageViewWithPost(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          $this->assertNotNull($event->getData()['target']);
          $this->assertIsArray($event->getData()['target']);
          $this->assertCount(1, $event->getData()['target']);
          $this->assertEquals('post-uuid-123', $event->getData()['target'][0]->id);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, 'post');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with comment entity.
   */
  public function testTrackPageViewWithComment(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          $this->assertNotNull($event->getData()['target']);
          $this->assertIsArray($event->getData()['target']);
          $this->assertCount(1, $event->getData()['target']);
          $this->assertEquals('comment-uuid-123', $event->getData()['target'][0]->id);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, 'comment');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with overview page (no entity).
   */
  public function testTrackPageViewWithOverviewPage(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          // No target entity for overview pages.
          $this->assertNull($event->getData()['target']);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, NULL);
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with cron route.
   */
  public function testTrackPageViewWithCronRoute(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          // No target entity for cron routes.
          $this->assertNull($event->getData()['target']);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('application', $event->getData()['actor']);
          // Application actor for cron.
          $this->assertNotNull($event->getData()['actor']->application);
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, NULL, 'entity.ultimate_cron_job.run');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with profile entity (should not track target).
   */
  public function testTrackPageViewWithProfileEntity(): void {
    $dispatcher = $this->createMock(DispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isType('string'),
        $this->callback(function ($event) {
          $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
          $this->assertArrayHasKey('target', $event->getData());
          // Profile entities are excluded from tracking.
          $this->assertNull($event->getData()['target']);
          $this->assertArrayHasKey('actor', $event->getData());
          $this->assertObjectHasProperty('user', $event->getData()['actor']);
          $this->assertNotNull($event->getData()['actor']->user);
          return TRUE;
        })
      );

    $handler = $this->createEdaHandler($dispatcher, FALSE, 'profile');
    $handler->trackPageView();
  }

  /**
   * Create a mock EdaHandler for testing.
   *
   * @param \Drupal\social_eda\DispatcherInterface|null $dispatcher
   *   The dispatcher service.
   * @param bool $anonymous
   *   Whether the user is anonymous.
   * @param string|null $entity_type
   *   The entity type to mock in route parameters.
   * @param string $route_name
   *   The route name to mock.
   */
  protected function createEdaHandler($dispatcher = NULL, $anonymous = FALSE, $entity_type = 'node', $route_name = 'entity.node.canonical'): EdaHandler {
    // Only create a dispatcher mock if we need one and none was provided.
    if ($dispatcher === NULL && $entity_type !== 'no_dispatcher') {
      $dispatcher = $this->createMock(DispatcherInterface::class);
    }

    // Mock the UUID service.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Component\Uuid\UuidInterface $uuid */
    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn('test-uuid-123');

    // Mock the request stack.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\RequestStack $requestStack */
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($this->createRequest());

    // Mock the URL.
    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('https://example.com/test-page');

    // Mock the node interface.
    $node = $this->createMock(NodeInterface::class);
    $node->method('uuid')->willReturn('node-uuid-123');
    $node->method('toUrl')->willReturn($url);

    // Mock the route match.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Routing\RouteMatchInterface $routeMatch */
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteName')->willReturn($route_name);

    // Create a simple mock for route parameters.
    $parameterBag = $this->createMock(ParameterBag::class);

    if ($entity_type) {
      $parameterBag->method('has')->willReturnMap([
        ['node', $entity_type === 'node'],
        ['group', $entity_type === 'group'],
        ['user', $entity_type === 'user'],
        ['post', $entity_type === 'post'],
        ['comment', $entity_type === 'comment'],
        ['profile', $entity_type === 'profile'],
      ]);

      // Profile entities are excluded from tracking, so return NULL for them.
      if ($entity_type === 'profile') {
        $parameterBag->method('get')->willReturn(NULL);
      }
      else {
        $entity = $this->createMockEntity($entity_type);
        $parameterBag->method('get')->willReturn($entity);
      }
    }
    else {
      $parameterBag->method('has')->willReturn(FALSE);
    }

    $routeMatch->method('getParameters')->willReturn($parameterBag);

    // Mock the account proxy; ActorUser resolves UUID via getAccount().
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Session\AccountProxyInterface $account */
    $account = $this->createMock(AccountProxyInterface::class);
    $account->method('id')->willReturn($anonymous ? 0 : 1);
    $account->method('isAuthenticated')->willReturn(!$anonymous);
    $account->method('isAnonymous')->willReturn($anonymous);
    if (!$anonymous) {
      $account_actor = $this->createMock(UserInterface::class);
      $account_actor->method('uuid')->willReturn('user-uuid-123');
      $account_actor->method('getDisplayName')->willReturn('Test User');
      $account_actor->method('isAnonymous')->willReturn(FALSE);
      $account->method('getAccount')->willReturn($account_actor);
    }

    // Mock the config factory.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['namespace', 'com.getopensocial'],
      ['application_id', 'app-uuid-123'],
      ['application_name', 'Test App'],
    ]);
    $configFactory->method('get')->willReturn($config);

    // Mock the time service.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Component\Datetime\TimeInterface $time */
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1234567890);

    // Mock the logger factory.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory */
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    return new EdaHandler(
      $uuid,
      $requestStack,
      $account,
      $routeMatch,
      $configFactory,
      $time,
      $loggerFactory,
      $dispatcher
    );
  }

  /**
   * Create a mock entity for testing.
   *
   * @param string $entity_type
   *   The entity type to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The mocked entity.
   */
  protected function createMockEntity(string $entity_type): EntityInterface {
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('uuid')->willReturn($entity_type . '-uuid-123');
    $entity->method('getEntityTypeId')->willReturn($entity_type);

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('https://example.com/' . $entity_type);
    $entity->method('toUrl')->willReturn($url);

    return $entity;
  }

  /**
   * Create a mock request for testing.
   */
  protected function createRequest(): Request {
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\Request $request */
    $request = $this->createMock(Request::class);
    $request->method('getUri')->willReturn('https://example.com/test-page?param=value');
    $request->method('getPathInfo')->willReturn('/test-page');
    return $request;
  }

}
