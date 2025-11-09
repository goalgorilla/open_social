<?php

namespace Drupal\Tests\social_topic\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_topic\EdaHandler;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_topic\EdaHandler
 */
class EdaHandlerTest extends UnitTestCase {

  /**
   * Handles module-related operations.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Mocked dispatcher service for sending CloudEvents.
   */
  protected MockObject $dispatcher;

  /**
   * Handles HTTP request stack operations.
   */
  protected RequestStack $requestStack;

  /**
   * The project ID for deterministic UUID generation.
   */
  protected string $projectId = 'test-project-id';

  /**
   * Original settings saved before test modifications.
   *
   * Stores the Settings singleton state at the start of setUp() so it can be
   * restored in tearDown(). This ensures test isolation by preventing Settings
   * changes from affecting other tests.
   *
   * @var array
   */
  protected array $originalSettings = [];

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
   * Represents a taxonomy term.
   */
  protected TermInterface $topicTypeTerm;

  /**
   * Represents the topic type field, typically a taxonomy term.
   *
   * @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\taxonomy\TermInterface>
   */
  protected EntityReferenceFieldItemListInterface $topicTypeField;

  /**
   * Represents a list of field items, such as a reference to groups.
   *
   * @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\Core\Entity\EntityInterface>
   */
  protected EntityReferenceFieldItemListInterface $fieldItemList;

  /**
   * Represents a node entity.
   */
  protected NodeInterface $node;

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

    // Save current settings to restore in tearDown().
    // The Settings class is a singleton, so we need to preserve the existing
    // configuration before modifying it. This prevents test interference where
    // one test's Settings changes affect other tests.
    // Settings may not be initialized in unit tests, so handle that case.
    try {
      Settings::getInstance();
      $this->originalSettings = Settings::getAll();
    }
    catch (\BadMethodCallException $e) {
      // Settings not initialized yet, start with empty array.
      $this->originalSettings = [];
      new Settings([]);
    }

    // Merge project_id for deterministic UUID generation while preserving
    // other settings. This ensures we only override the project_id setting
    // needed for deterministic UUIDs, without losing any existing settings
    // that other tests might depend on.
    $mergedSettings = array_merge($this->originalSettings, ['project_id' => $this->projectId]);
    new Settings($mergedSettings);

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

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $entityStorageMock = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('user')->willReturn($entityStorageMock);

    // Mock the AccountProxyInterface.
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->account->method('id')->willReturn(1);

    // Mock the RouteMatchInterface.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.edit_form');

    // Mock the Request.
    $this->request = $this->createMock(Request::class);
    $this->request->method('getUri')->willReturn('http://example.com/node/add/topic');
    $this->request->method('getPathInfo')->willReturn('/node/add/topic');

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

    // Mock the field_topic_type.
    $this->topicTypeTerm = $this->createMock(TermInterface::class);
    $this->topicTypeTerm->method('label')->willReturn('Topic Type Label');

    $this->topicTypeField = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $this->topicTypeField->method('isEmpty')->willReturn(FALSE);
    $this->topicTypeField->method('getEntity')->willReturn($this->topicTypeTerm);
    $this->topicTypeField->method('referencedEntities')->willReturn([$this->topicTypeTerm]);

    // Mock the FieldItemListInterface.
    $this->fieldItemList = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $this->fieldItemList->method('isEmpty')->willReturn(FALSE);
    $this->fieldItemList->method('getEntity')->willReturn($this->entityInterface);
    $this->fieldItemList->method('referencedEntities')->willReturn([$this->entityInterface]);

    // Mock the Node.
    $this->node = $this->createMock(NodeInterface::class);
    $this->node->method('label')->willReturn('Topic Title');
    $this->node->method('getCreatedTime')->willReturn(1692614400);
    $this->node->method('getChangedTime')->willReturn(1692618000);
    $this->node->method('getRevisionId')->willReturn(1);
    $this->node->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->node->method('id')->willReturn(123);
    $this->node->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_content_visibility', 'groups', 'field_topic_type']);
    });
    $this->node->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'groups') {
        return $this->fieldItemList;
      }
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      if ($field_name === 'status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_content_visibility') {
        return (object) ['value' => 'public'];
      }
      if ($field_name === 'uid') {
        return (object) ['entity' => $this->userInterface];
      }
      if ($field_name === 'field_topic_type') {
        return $this->topicTypeField;
      }
      return NULL;
    });
    $this->node->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the CloudEvent class.
    $this->cloudEvent = $this->createMock(CloudEventInterface::class);

    // Initialize the time service.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_topic')->willReturn($this->logger);
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
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.topic.create', $event->getType());
    $this->assertEquals('/node/add/topic', $event->getSource());
    $this->assertEquals('e6cf07d6-f342-5935-bef6-9c913b26c15a', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test generateEventId for create event.
   *
   * @covers \Drupal\social_topic\EdaHandler::fromEntity
   * @covers \Drupal\social_topic\EdaHandler::generateEventId
   */
  public function testGenerateEventIdCreate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.create');

    $this->assertEquals('e6cf07d6-f342-5935-bef6-9c913b26c15a', $event->getId());
  }

  /**
   * Test generateEventId for delete event.
   *
   * @covers \Drupal\social_topic\EdaHandler::fromEntity
   * @covers \Drupal\social_topic\EdaHandler::generateEventId
   */
  public function testGenerateEventIdDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.delete', 'delete');

    $this->assertEquals('383fe1cc-5d5f-5ce2-883d-ba3537990f0f', $event->getId());
  }

  /**
   * Test generateEventId for publish event (includes revision ID).
   *
   * @covers \Drupal\social_topic\EdaHandler::fromEntity
   * @covers \Drupal\social_topic\EdaHandler::generateEventId
   */
  public function testGenerateEventIdPublish(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.publish');

    $this->assertEquals('e32b892c-1db6-5cf6-8294-5795a57b259a', $event->getId());
  }

  /**
   * Test generateEventId for unpublish event (includes revision ID).
   *
   * @covers \Drupal\social_topic\EdaHandler::fromEntity
   * @covers \Drupal\social_topic\EdaHandler::generateEventId
   */
  public function testGenerateEventIdUnpublish(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.unpublish');

    $this->assertEquals('3be8b97e-5591-5c90-9af5-1c15f5907eba', $event->getId());
  }

  /**
   * Test generateEventId for update event (includes revision ID).
   *
   * @covers \Drupal\social_topic\EdaHandler::fromEntity
   * @covers \Drupal\social_topic\EdaHandler::generateEventId
   */
  public function testGenerateEventIdUpdate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.update');

    $this->assertEquals('886a9c13-4089-565c-8689-209c2a8ca875', $event->getId());
  }

  /**
   * Test the topicCreate() method.
   *
   * @covers ::topicCreate
   */
  public function testTopicCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.topic.v1'),
        $this->equalTo($event)
      );

    // Call the topicCreate method.
    $handler->topicCreate($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.topic.create', $event->getType());
  }

  /**
   * Test the topicPublish() method.
   *
   * @covers ::topicPublish
   */
  public function testTopicPublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.publish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.topic.v1'),
        $this->equalTo($event)
      );

    // Call the topicPublish method.
    $handler->topicPublish($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.topic.publish', $event->getType());
  }

  /**
   * Test the topicUnpublish() method.
   *
   * @covers ::topicUnpublish
   */
  public function testTopicUnpublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.unpublish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.topic.v1'),
        $this->equalTo($event)
      );

    // Call the topicUnpublish method.
    $handler->topicUnpublish($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.topic.unpublish', $event->getType());
  }

  /**
   * Test the topicUpdate() method.
   *
   * @covers ::topicUpdate
   */
  public function testTopicUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.topic.v1'),
        $this->equalTo($event)
      );

    // Call the topicUpdate method.
    $handler->topicUpdate($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.topic.update', $event->getType());
  }

  /**
   * Test the topicDelete() method.
   *
   * @covers ::topicDelete
   */
  public function testTopicDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.topic.delete', 'delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.topic.v1'),
        $this->equalTo($event)
      );

    // Call the topicDelete method.
    $handler->topicDelete($this->node);
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_topic\EdaHandler
   *   The mocked handler instance.
   */
  protected function getMockedHandler(): EdaHandler {
    return new EdaHandler(
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      // @phpstan-ignore-next-line
      $this->dispatcher,
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    // Restore original settings so other tests retain their configuration.
    // This ensures that any Settings modifications made during this test
    // don't leak into subsequent tests, maintaining test isolation.
    new Settings($this->originalSettings);

    parent::tearDown();
  }

}
