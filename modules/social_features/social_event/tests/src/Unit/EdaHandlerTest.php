<?php

namespace Drupal\Tests\social_event\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
use Drupal\social_event\EdaHandler;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_event\EdaHandler
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
   * Represents an address field item.
   */
  protected AddressItem $addressItem;

  /**
   * Represents a list of address field items.
   */
  protected AddressFieldItemList $addressItemList;

  /**
   * Represents a taxonomy term.
   */
  protected TermInterface $eventTypeTerm;

  /**
   * Represents the event type field, typically a taxonomy term.
   *
   * @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\taxonomy\TermInterface>
   */
  protected EntityReferenceFieldItemListInterface $eventTypeField;

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
    $this->request->method('getUri')->willReturn('http://example.com/node/add/event');
    $this->request->method('getPathInfo')->willReturn('/node/add/event');

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

    // Mock Address field.
    $this->addressItem = $this->createMock(AddressItem::class);
    $this->addressItemList = $this->createMock(AddressFieldItemList::class);
    $this->addressItemList->method('first')->willReturn($this->addressItem);

    // Mock the field_event_type.
    $this->eventTypeTerm = $this->createMock(TermInterface::class);
    $this->eventTypeTerm->method('label')->willReturn('Term Label');

    $this->eventTypeField = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $this->eventTypeField->method('isEmpty')->willReturn(FALSE);
    $this->eventTypeField->method('getEntity')->willReturn($this->eventTypeTerm);
    $this->eventTypeField->method('referencedEntities')->willReturn([$this->eventTypeTerm]);

    // Mock the FieldItemListInterface.
    $this->fieldItemList = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $this->fieldItemList->method('isEmpty')->willReturn(FALSE);
    $this->fieldItemList->method('getEntity')->willReturn($this->entityInterface);
    $this->fieldItemList->method('referencedEntities')->willReturn([$this->entityInterface]);

    // Mock the Node.
    $this->node = $this->createMock(NodeInterface::class);
    $this->node->method('label')->willReturn('Event Title');
    $this->node->method('getCreatedTime')->willReturn(1692614400);
    $this->node->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_content_visibility', 'groups', 'field_event_type']);
    });
    $this->node->method('getChangedTime')->willReturn(1692618000);
    $this->node->method('getRevisionId')->willReturn(1);
    $this->node->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
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
      if ($field_name === 'field_event_all_day') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_event_date') {
        return (object) ['value' => '2024-08-21T10:00:00'];
      }
      if ($field_name === 'field_event_date_end') {
        return (object) ['value' => '2024-08-21T10:00:00'];
      }
      if ($field_name === 'field_event_address') {
        return $this->addressItemList;
      }
      if ($field_name === 'field_event_location') {
        return (object) ['value' => 'Location Label'];
      }
      if ($field_name === 'field_event_enroll') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_enroll_method') {
        return (object) ['value' => 0];
      }
      if ($field_name === 'field_event_type') {
        return $this->eventTypeField;
      }
      if ($field_name === 'uid') {
        return (object) ['entity' => $this->userInterface];
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
    $this->loggerFactory->method('get')->with('social_event')->willReturn($this->logger);
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
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.event.create', $event->getType());
    $this->assertEquals('/node/add/event', $event->getSource());
    $this->assertEquals('3fe76ad5-2f82-52e3-b240-9deb1abd2bf2', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test generateEventId for create event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdCreate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.create');

    $this->assertEquals('3fe76ad5-2f82-52e3-b240-9deb1abd2bf2', $event->getId());
  }

  /**
   * Test generateEventId for delete event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.delete', 'delete');

    $this->assertEquals('6344f84d-488c-5098-bbed-b0b71d717116', $event->getId());
  }

  /**
   * Test generateEventId for publish event (includes revision ID).
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdPublish(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.publish');

    $this->assertEquals('7f0df8cd-70d9-5d57-87fb-226e190586b3', $event->getId());
  }

  /**
   * Test generateEventId for unpublish event (includes revision ID).
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdUnpublish(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.unpublish');

    $this->assertEquals('34128b44-4f01-57a2-a3c4-257b27882a10', $event->getId());
  }

  /**
   * Test generateEventId for update event (includes revision ID).
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdUpdate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.update');

    $this->assertEquals('c39f4e44-efb0-546d-9fa0-db63d94fd61a', $event->getId());
  }

  /**
   * Test the eventCreate() method.
   *
   * @covers ::eventCreate
   */
  public function testEventCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event.v1'),
        $this->equalTo($event)
      );

    // Call the eventCreate method.
    $handler->eventCreate($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.event.create', $event->getType());
  }

  /**
   * Test the eventDelete() method.
   *
   * @covers ::eventDelete
   */
  public function testEventDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.delete', 'delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event.v1'),
        $this->equalTo($event)
      );

    // Call the eventDelete method.
    $handler->eventDelete($this->node);
  }

  /**
   * Test the $this->eventUnpublish() method.
   *
   * @covers ::eventUnpublish
   */
  public function testEventUnpublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.unpublish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event.v1'),
        $this->equalTo($event)
      );

    // Call the eventCreate method.
    $handler->eventUnpublish($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.event.unpublish', $event->getType());
  }

  /**
   * Test the $this->eventPublish() method.
   *
   * @covers ::eventPublish
   */
  public function testEventPublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.publish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event.v1'),
        $this->equalTo($event)
      );

    // Call the eventCreate method.
    $handler->eventPublish($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.event.publish', $event->getType());
  }

  /**
   * Test the eventUpdate() method.
   *
   * @covers ::eventUpdate
   */
  public function testEventUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event.v1'),
        $this->equalTo($event)
      );

    // Call the eventCreate method.
    $handler->eventUpdate($this->node);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.event.update', $event->getType());
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_event\EdaHandler
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
      $this->dispatcher
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
