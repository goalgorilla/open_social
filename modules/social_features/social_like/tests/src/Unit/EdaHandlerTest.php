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
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\social_eda\DispatcherInterface
   */
  protected $dispatcher;

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
    $languageMock = $this->createMock(LanguageInterface::class);
    $languageMock->method('getId')->willReturn('en');
    $languageManagerMock = $this->createMock(LanguageManagerInterface::class);
    $languageManagerMock->method('getCurrentLanguage')->willReturn($languageMock);

    // Mock the configuration for `social_eda.settings.namespaces`.
    $configMock = $this->createMock(ImmutableConfig::class);
    $configMock->method('get')->with('namespace')->willReturn('com.getopensocial');

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->with('social_eda.settings')->willReturn($configMock);

    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    $container->set('language_manager', $languageManagerMock);
    \Drupal::setContainer($container);

    // Mock the module handler and ensure `social_eda` is enabled.
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->method('moduleExists')->with('social_eda')->willReturn(TRUE);

    // Mock the Dispatcher service.
    $this->dispatcher = $this->createMock(DispatcherInterface::class);

    // Mock the AccountProxyInterface.
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->account->method('id')->willReturn(1);

    // Mock the RouteMatchInterface.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');

    // Mock the UUID.
    $this->uuid = $this->createMock(UuidInterface::class);
    $this->uuid->method('generate')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');

    // Create a real Symfony Request instance.
    $this->request = Request::create(
      'http://example.com/node/1',
      'GET',
      [],
      [],
      [],
      ['HTTP_REFERER' => 'http://example.com/node/1']
    );

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturn($this->request);

    // Mock the URL object.
    $this->url = $this->createMock(Url::class);
    $this->url->method('toString')->willReturn('http://example.com');

    // Mock the EntityInterface.
    $this->entityInterface = $this->createMock(EntityInterface::class);
    $this->entityInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $this->entityInterface->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->entityInterface->method('label')->willReturn('Test Entity');

    // Mock the UserInterface.
    $this->userInterface = $this->createMock(UserInterface::class);
    $this->userInterface->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->userInterface->method('getDisplayName')->willReturn('User name');
    $this->userInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Node.
    $this->node = $this->createMock(NodeInterface::class);
    $this->node->method('label')->willReturn('Event Title');
    $this->node->method('getCreatedTime')->willReturn(1692614400);
    $this->node->method('getChangedTime')->willReturn(1692618000);
    $this->node->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->node->method('get')
      ->willReturnCallback(function ($field_name) {
        if ($field_name === 'uuid') {
          return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
        }
        if ($field_name === 'status') {
          return (object) ['value' => 1];
        }
        if ($field_name === 'uid') {
          return (object) ['entity' => $this->userInterface];
        }
        return NULL;
      });
    $this->node->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $this->node->method('getEntityTypeId')->willReturn('node');
    $this->node->method('bundle')->willReturn('event');

    // Mock the Vote (like).
    $this->vote = $this->createMock(VoteInterface::class);
    $this->vote->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->vote->method('getCreatedTime')->willReturn(1692614400);
    $this->vote->method('getVotedEntityType')->willReturn('node');
    $this->vote->method('getVotedEntityId')->willReturn(1);
    $this->vote->method('bundle')->willReturn('like');
    $this->vote->method('getOwner')->willReturn($this->userInterface);

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $userStorageMock = $this->createMock(EntityStorageInterface::class);
    $nodeStorageMock = $this->createMock(EntityStorageInterface::class);
    $nodeStorageMock->method('load')->with(1)->willReturn($this->node);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($userStorageMock, $nodeStorageMock) {
        if ($entity_type === 'user') {
          return $userStorageMock;
        }
        if ($entity_type === 'node') {
          return $nodeStorageMock;
        }
        return NULL;
      });

    // Mock the CloudEvent class.
    $this->cloudEvent = $this->createMock(CloudEventInterface::class);

    // Initialize the time service.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_like')->willReturn($this->logger);
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
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.like.v1'),
        $this->callback(function ($event) {
          return $event->getType() === 'com.getopensocial.cms.like.create';
        })
      );

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
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.like.v1'),
        $this->callback(function ($event) {
          return $event->getType() === 'com.getopensocial.cms.like.delete';
        })
      );

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
    $moduleHandlerMock = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandlerMock->method('moduleExists')->with('social_eda')->willReturn(FALSE);

    $handler = new EdaHandler(
      $this->uuid,
      $this->requestStack,
      $moduleHandlerMock,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher
    );

    // Expect dispatcher NOT to be called.
    $this->dispatcher->expects($this->never())
      ->method('dispatch');

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
