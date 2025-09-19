<?php

namespace Drupal\Tests\social_like\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_like\EdaHandler;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\votingapi\VoteInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_like\EdaHandler
 */
class EdaHandlerTest extends UnitTestCase {

  /**
   * Handles module-related operations.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Mocked dispatcher service for sending CloudEvents.
   */
  protected DispatcherInterface $dispatcher;

  /**
   * The prophesized dispatcher for expectations.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $dispatcherProphecy;

  /**
   * Handles UUID generation.
   */
  protected UuidInterface $uuid;

  /**
   * Handles HTTP request stack operations.
   */
  protected RequestStack $requestStack;

  /**
   * Represents the canonical URL of an entity.
   */
  protected Url $url;

  /**
   * Represents a generic entity in Drupal.
   */
  protected EntityInterface $entityInterface;

  /**
   * Represents a user entity.
   */
  protected UserInterface $userInterface;

  /**
   * Represents a node entity (liked entity).
   */
  protected NodeInterface $node;

  /**
   * Represents a vote entity (like).
   */
  protected VoteInterface $vote;

  /**
   * Represents an HTTP request.
   */
  protected Request $request;

  /**
   * Manages entity types and their storage handlers.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Represents the route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Represents the account proxy.
   */
  protected AccountProxyInterface $account;

  /**
   * Represents the CloudEvent.
   */
  protected CloudEventInterface $cloudEvent;

  /**
   * Represents the ConfigFactoryInterface.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the language_manager service.
    $languageManagerMock = $this->prophesize(LanguageManagerInterface::class);
    $languageMock = $this->prophesize(LanguageInterface::class);
    $languageMock->getId()->willReturn('en');
    $languageManagerMock->getCurrentLanguage()
      ->willReturn($languageMock->reveal());

    // Mock the configuration for `social_eda.settings.namespaces`.
    $configMock = $this->prophesize(ImmutableConfig::class);
    $configMock->get('namespace')->willReturn('com.getopensocial');

    $configFactoryMock = $this->prophesize(ConfigFactoryInterface::class);
    $configFactoryMock->get('social_eda.settings')->willReturn($configMock->reveal());
    $this->configFactory = $configFactoryMock->reveal();

    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactoryMock->reveal());

    // Mock Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock->reveal());
    \Drupal::setContainer($container);

    // Prophesize the module handler and ensure `social_eda` is enabled.
    $moduleHandlerProphecy = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandlerProphecy->moduleExists('social_eda')->willReturn(TRUE);
    $this->moduleHandler = $moduleHandlerProphecy->reveal();

    // Prophesize the Dispatcher service.
    $this->dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
    $this->dispatcher = $this->dispatcherProphecy->reveal();

    // Prophesize the AccountProxyInterface.
    $accountMock = $this->prophesize(AccountProxyInterface::class);
    $accountMock->id()->willReturn(1);
    $this->account = $accountMock->reveal();

    // Prophesize the RouteMatchInterface.
    $routeMatchMock = $this->prophesize(RouteMatchInterface::class);
    $routeMatchMock->getRouteName()->willReturn('entity.node.canonical');
    $this->routeMatch = $routeMatchMock->reveal();

    // Prophesize the UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Create a real Symfony Request instance.
    $this->request = Request::create(
      'http://example.com/node/1',
      'GET',
      [],
      [],
      [],
      ['HTTP_REFERER' => 'http://example.com/node/1']
    );

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Prophesize the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Prophesize the EntityInterface.
    $entityMock = $this->prophesize(EntityInterface::class);
    $entityMock->toUrl('canonical', ['absolute' => TRUE])
      ->willReturn($this->url);
    $entityMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $entityMock->label()->willReturn('Test Entity');
    $this->entityInterface = $entityMock->reveal();

    // Prophesize the UserInterface.
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $userMock->getDisplayName()->willReturn('User name');
    $userMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $this->userInterface = $userMock->reveal();

    // Prophesize the Node.
    $nodeMock = $this->prophesize(NodeInterface::class);
    $nodeMock->label()->willReturn('Event Title');
    $nodeMock->getCreatedTime()->willReturn(1692614400);
    $nodeMock->getChangedTime()->willReturn(1692618000);
    $nodeMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $nodeMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $nodeMock->get('status')->willReturn((object) ['value' => 1]);
    $nodeMock->get('uid')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $nodeMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $nodeMock->getEntityTypeId()->willReturn('node');
    $nodeMock->bundle()->willReturn('event');
    $this->node = $nodeMock->reveal();

    // Prophesize the Vote (like).
    $voteMock = $this->prophesize(VoteInterface::class);
    $voteMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $voteMock->getCreatedTime()->willReturn(1692614400);
    $voteMock->getVotedEntityType()->willReturn('node');
    $voteMock->getVotedEntityId()->willReturn(1);
    $voteMock->bundle()->willReturn('like');
    $voteMock->getOwner()->willReturn($this->userInterface);
    $this->vote = $voteMock->reveal();

    // Prophesize the EntityTypeManagerInterface and the corresponding storage.
    $userStorageMock = $this->prophesize(EntityStorageInterface::class);
    $nodeStorageMock = $this->prophesize(EntityStorageInterface::class);
    $nodeStorageMock->load(1)->willReturn($this->node);

    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('user')
      ->willReturn($userStorageMock->reveal());
    $entityTypeManagerMock->getStorage('node')
      ->willReturn($nodeStorageMock->reveal());
    $this->entityTypeManager = $entityTypeManagerMock->reveal();

    // Prophesize the CloudEvent class.
    $cloudEventMock = $this->prophesize(CloudEventInterface::class);
    $this->cloudEvent = $cloudEventMock->reveal();

    // Initialize the time service.
    $timeMock = $this->prophesize(TimeInterface::class);
    $timeMock->getRequestTime()->willReturn(1234567890);
    $this->time = $timeMock->reveal();

    // Initialize the logger.
    $loggerMock = $this->prophesize(LoggerChannelInterface::class);
    $this->logger = $loggerMock->reveal();

    $loggerFactoryMock = $this->prophesize(LoggerChannelFactoryInterface::class);
    $loggerFactoryMock->get('social_like')->willReturn($this->logger);
    $this->loggerFactory = $loggerFactoryMock->reveal();
  }

  /**
   * Test method fromEntity().
   *
   * @covers ::fromEntity
   */
  public function testFromEntity(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->vote, 'com.getopensocial.cms.like.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.like.create', $event->getType());
    $this->assertEquals('/node/1', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test the likeCreate() method.
   *
   * @covers ::likeCreate
   */
  public function testLikeCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Expect the dispatch method in the dispatcher to be called with correct
    // topic and event type.
    $this->dispatcherProphecy->dispatch(
      'com.getopensocial.cms.like.v1',
      Argument::that(function ($event) {
        return $event->getType() === 'com.getopensocial.cms.like.create';
      })
    )->shouldBeCalled();

    // Call the likeCreate method.
    $handler->likeCreate($this->vote);

    // Assert that the correct event type is dispatched.
    $this->assertEquals('com.getopensocial.cms.like.create', $handler->fromEntity($this->vote, 'com.getopensocial.cms.like.create')->getType());
  }

  /**
   * Test the likeDelete() method.
   *
   * @covers ::likeDelete
   */
  public function testLikeDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Expect the dispatch method in the dispatcher to be called with correct
    // topic and event type.
    $this->dispatcherProphecy->dispatch(
      'com.getopensocial.cms.like.v1',
      Argument::that(function ($event) {
        return $event->getType() === 'com.getopensocial.cms.like.delete';
      })
    )->shouldBeCalled();

    // Call the likeDelete method.
    $handler->likeDelete($this->vote);

    // Assert that the correct event type is dispatched.
    $this->assertEquals('com.getopensocial.cms.like.delete', $handler->fromEntity($this->vote, 'com.getopensocial.cms.like.delete')->getType());
  }

  /**
   * Test that events are not dispatched when social_eda module is disabled.
   *
   * @covers ::likeCreate
   */
  public function testNoDispatchWhenModuleDisabled(): void {
    // Create a new handler with module disabled.
    $moduleHandlerProphecy = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandlerProphecy->moduleExists('social_eda')->willReturn(FALSE);

    $handler = new EdaHandler(
      $this->uuid,
      $this->requestStack,
      $moduleHandlerProphecy->reveal(),
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher
    );

    // Expect dispatcher NOT to be called.
    $this->dispatcherProphecy->dispatch(
      'com.getopensocial.cms.like.v1',
      Argument::any()
    )->shouldNotBeCalled();

    // Call the method.
    $handler->likeCreate($this->vote);
  }

  /**
   * Test that events are not dispatched when dispatcher is NULL.
   *
   * @covers ::likeCreate
   */
  public function testNoDispatchWhenDispatcherIsNull(): void {
    // Create handler without dispatcher.
    $handler = new EdaHandler(
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      NULL
    );

    // Call the method - should not throw any exceptions.
    $handler->likeCreate($this->vote);
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_like\EdaHandler
   *   The mocked handler instance.
   */
  protected function getMockedHandler(): EdaHandler {
    return new EdaHandler(
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher,
    );
  }

}
