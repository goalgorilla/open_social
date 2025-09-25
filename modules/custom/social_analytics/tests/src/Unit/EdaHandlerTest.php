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
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
use Prophecy\Argument;
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
    $languageManagerMock = $this->prophesize(LanguageManagerInterface::class);
    $languageMock = $this->prophesize(LanguageInterface::class);
    $languageMock->getId()->willReturn('en');
    $languageManagerMock->getCurrentLanguage()->willReturn($languageMock->reveal());

    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Test constructor with authenticated user.
   */
  public function testConstructorWithAuthenticatedUser(): void {
    $handler = $this->createEdaHandler();
    $this->assertInstanceOf(EdaHandler::class, $handler);
  }

  /**
   * Test trackPageView with authenticated user.
   */
  public function testTrackPageViewWithAuthenticatedUser(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce();

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal());
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with anonymous user.
   */
  public function testTrackPageViewWithAnonymousUser(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), TRUE);
    $handler->trackPageView();
  }

  /**
   * Test fromPageView with canonical URL.
   */
  public function testFromPageViewWithCanonicalUrl(): void {
    $handler = $this->createEdaHandler();
    $request = $this->createRequest();

    $event = $handler->fromPageView($request, 'com.getopensocial.cms.page_view');

    $this->assertInstanceOf(CloudEventInterface::class, $event);
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

    $this->assertInstanceOf(CloudEventInterface::class, $event);
    $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
    $this->assertEquals('https://example.com/test-page?param=value', $event->getData()['url']);
  }

  /**
   * Test dispatch with exception handling.
   */
  public function testDispatchWithException(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::any(), Argument::any())
      ->willThrow(new \Exception('Test exception'));

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal());

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
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        $this->assertNotNull($event->getData()['target']);
        $this->assertIsArray($event->getData()['target']);
        $this->assertCount(1, $event->getData()['target']);
        $this->assertEquals('group-uuid-123', $event->getData()['target'][0]->id);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, 'group');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with user entity.
   */
  public function testTrackPageViewWithUser(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        $this->assertNotNull($event->getData()['target']);
        $this->assertIsArray($event->getData()['target']);
        $this->assertCount(1, $event->getData()['target']);
        $this->assertEquals('user-uuid-123', $event->getData()['target'][0]->id);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, 'user');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with post entity.
   */
  public function testTrackPageViewWithPost(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        $this->assertNotNull($event->getData()['target']);
        $this->assertIsArray($event->getData()['target']);
        $this->assertCount(1, $event->getData()['target']);
        $this->assertEquals('post-uuid-123', $event->getData()['target'][0]->id);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, 'post');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with comment entity.
   */
  public function testTrackPageViewWithComment(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        $this->assertNotNull($event->getData()['target']);
        $this->assertIsArray($event->getData()['target']);
        $this->assertCount(1, $event->getData()['target']);
        $this->assertEquals('comment-uuid-123', $event->getData()['target'][0]->id);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, 'comment');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with overview page (no entity).
   */
  public function testTrackPageViewWithOverviewPage(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        // No target entity for overview pages.
        $this->assertNull($event->getData()['target']);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, NULL);
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with cron route.
   */
  public function testTrackPageViewWithCronRoute(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        // No target entity for cron routes.
        $this->assertNull($event->getData()['target']);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('application', $event->getData()['actor']);
        // Application actor for cron.
        $this->assertNotNull($event->getData()['actor']['application']);
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, NULL, 'entity.ultimate_cron_job.run');
    $handler->trackPageView();
  }

  /**
   * Test trackPageView with profile entity (should not track target).
   */
  public function testTrackPageViewWithProfileEntity(): void {
    $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $dispatcherProphecy->dispatch(Argument::type('string'), Argument::type(CloudEventInterface::class))
      ->shouldBeCalledOnce()
      ->will(function ($args) {
        // The CloudEvent.
        $event = $args[1];
        $this->assertInstanceOf(CloudEventInterface::class, $event);
        $this->assertEquals('com.getopensocial.cms.page_view', $event->getType());
        $this->assertArrayHasKey('target', $event->getData());
        // Profile entities are excluded from tracking.
        $this->assertNull($event->getData()['target']);
        $this->assertArrayHasKey('actor', $event->getData());
        $this->assertArrayHasKey('user', $event->getData()['actor']);
        $this->assertNotNull($event->getData()['actor']['user']);
      });

    $handler = $this->createEdaHandler($dispatcherProphecy->reveal(), FALSE, 'profile');
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
    // Only create a dispatcher prophecy if we need one and none was provided.
    if ($dispatcher === NULL && $entity_type !== 'no_dispatcher') {
      $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
      $dispatcher = $dispatcherProphecy->reveal();
    }

    // Mock the UUID service.
    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn('test-uuid-123');

    // Mock the request stack.
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($this->createRequest());

    // Mock the URL.
    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('https://example.com/test-page');

    // Mock the user interface.
    $userInterface = $this->createMock(UserInterface::class);
    $userInterface->method('uuid')->willReturn('user-uuid-123');
    $userInterface->method('getDisplayName')->willReturn('Test User');
    $userInterface->method('toUrl')->willReturn($url);

    // Mock the node interface.
    $node = $this->createMock(NodeInterface::class);
    $node->method('uuid')->willReturn('node-uuid-123');
    $node->method('toUrl')->willReturn($url);

    // Mock the entity type manager.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')->willReturn($anonymous ? NULL : $userInterface);
    $entityTypeManager->method('getStorage')->willReturn($userStorage);

    // Mock the route match.
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

    // Mock the account proxy.
    $account = $this->createMock(AccountProxyInterface::class);
    $account->method('id')->willReturn($anonymous ? 0 : 1);
    $account->method('isAuthenticated')->willReturn(!$anonymous);

    // Mock the config factory.
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['namespace', 'com.getopensocial'],
      ['application_id', 'app-uuid-123'],
      ['application_name', 'Test App'],
    ]);
    $configFactory->method('get')->willReturn($config);

    // Mock the time service.
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1234567890);

    // Mock the logger factory.
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    return new EdaHandler(
      $uuid,
      $requestStack,
      $entityTypeManager,
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
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
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
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->createMock(Request::class);
    $request->method('getUri')->willReturn('https://example.com/test-page?param=value');
    $request->method('getPathInfo')->willReturn('/test-page');
    return $request;
  }

}
