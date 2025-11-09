<?php

namespace Drupal\Tests\social_event\Unit;

use CloudEvents\V1\CloudEventInterface;
use Consolidation\Config\ConfigInterface;
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
use Drupal\social_event\EdaEventEnrollmentHandler;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_event\EdaEventEnrollmentHandler
 */
class EdaEventEnrollmentHandlerTest extends UnitTestCase {

  /**
   * Handles module-related operations.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Mocked dispatcher service for sending CloudEvents.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\social_eda\DispatcherInterface
   */
  protected $dispatcher;

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
   * Represents a event enrollment entity.
   */
  protected EventEnrollmentInterface $eventEnrollment;

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
   * Represents the TimeInterface.
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
    $configMock = $this->createMock(ConfigInterface::class);
    $configMock->method('get')->with('namespace')->willReturn('com.getopensocial');

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->with('social_eda.settings')->willReturn($configMock);

    // Mock Drupal's container.
    $container = new ContainerBuilder();
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
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.edit_form');

    // Mock the TimeInterface.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_event')->willReturn($this->logger);

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
    $this->userInterface->method('id')->willReturn(10);
    $this->userInterface->method('getDisplayName')->willReturn('User name');
    $this->userInterface->method('getEmail')->willReturn('user@example.com');
    $this->userInterface->method('isAnonymous')->willReturn(FALSE);
    $this->userInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $entityStorageMock = $this->createMock(EntityStorageInterface::class);
    $entityStorageMock->method('load')->with(1)->willReturn($this->userInterface);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('user')->willReturn($entityStorageMock);

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

    // Mock the Event.
    $nodeMock = $this->createMock(NodeInterface::class);
    $nodeMock->method('label')->willReturn('Event Title');
    $nodeMock->method('getCreatedTime')->willReturn(1692614400);
    $nodeMock->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_content_visibility', 'groups', 'field_event_type']);
    });
    $nodeMock->method('getChangedTime')->willReturn(1692618000);
    $nodeMock->method('get')->willReturnCallback(function ($field_name) {
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
    $nodeMock->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Event Enrollment.
    $this->eventEnrollment = $this->createMock(EventEnrollmentInterface::class);
    $this->eventEnrollment->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'field_request_or_invite_status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_first_name') {
        return (object) ['value' => 'First name'];
      }
      if ($field_name === 'field_last_name') {
        return (object) ['value' => 'Last name'];
      }
      if ($field_name === 'field_email') {
        return (object) ['value' => 'test@test.com'];
      }
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      return NULL;
    });
    $this->eventEnrollment->method('getCreatedTime')->willReturn(1692614400);
    $this->eventEnrollment->method('getChangedTime')->willReturn(1692614400);
    $this->eventEnrollment->method('getEvent')->willReturn($nodeMock);
    $this->eventEnrollment->method('getAccountEntity')->willReturn($this->userInterface);
    $this->eventEnrollment->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');

    // Mock the CloudEvent class.
    $this->cloudEvent = $this->createMock(CloudEventInterface::class);
  }

  /**
   * Tests the eventEnrollmentCreate method.
   *
   * @covers ::eventEnrollmentCreate
   */
  public function testEventEnrollmentCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.create');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventEnrollmentCreate method.
    $handler->eventEnrollmentCreate($this->eventEnrollment);
  }

  /**
   * Tests the eventEnrollmentCancel method.
   *
   * @covers ::eventEnrollmentCancel
   */
  public function testEventEnrollmentCancel(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.delete');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventEnrollmentCancel method.
    $handler->eventEnrollmentCancel($this->eventEnrollment);
  }

  /**
   * Tests the eventRequestToJoin method.
   *
   * @covers ::eventRequestToJoin
   */
  public function testEventRequestToJoin(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.create');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventRequestToJoin method.
    $handler->eventRequestToJoin($this->eventEnrollment);
  }

  /**
   * Tests the eventRequestToJoinCancelled method.
   *
   * @covers ::eventRequestToJoinCancelled
   */
  public function testEventRequestToJoinCancelled(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.delete');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventRequestToJoinCancelled method.
    $handler->eventRequestToJoinCancelled($this->eventEnrollment);
  }

  /**
   * Tests the eventRequestToJoinAccepted method.
   *
   * @covers ::eventRequestToJoinAccepted
   */
  public function testEventRequestToJoinAccepted(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.accept');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventRequestToJoinAccepted method.
    $handler->eventRequestToJoinAccepted($this->eventEnrollment);
  }

  /**
   * Tests the eventRequestToJoinDeclined method.
   *
   * @covers ::eventRequestToJoinDeclined
   */
  public function testEventRequestToJoinDeclined(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.decline');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventRequestToJoinDeclined method.
    $handler->eventRequestToJoinDeclined($this->eventEnrollment);
  }

  /**
   * Tests the eventInviteToJoin method.
   *
   * @covers ::eventInviteToJoin
   */
  public function testEventInviteToJoin(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.create');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventInviteToJoin method.
    $handler->eventInviteToJoin($this->eventEnrollment);
  }

  /**
   * Tests the eventInviteToJoinCancelled method.
   *
   * @covers ::eventInviteToJoinCancelled
   */
  public function testEventInviteToJoinCancelled(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.delete');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventInviteToJoinCancelled method.
    $handler->eventInviteToJoinCancelled($this->eventEnrollment);
  }

  /**
   * Tests the eventInviteToJoinAccepted method.
   *
   * @covers ::eventInviteToJoinAccepted
   */
  public function testEventInviteToJoinAccepted(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.accept');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventInviteToJoinAccepted method.
    $handler->eventInviteToJoinAccepted($this->eventEnrollment);
  }

  /**
   * Tests the eventInviteToJoinDeclined method.
   *
   * @covers ::eventInviteToJoinDeclined
   */
  public function testEventInviteToJoinDeclined(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.decline');

    // Expect dispatch to be called with specific parameters.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.event_enrollment.v1'),
        $this->equalTo($event)
      );

    // Call the eventInviteToJoinDeclined method.
    $handler->eventInviteToJoinDeclined($this->eventEnrollment);
  }

  /**
   * Tests the fromEntity method.
   *
   * @covers ::fromEntity
   */
  public function testFromEntity(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity(
      $this->eventEnrollment,
      'com.getopensocial.cms.event_enrollment.create'
    );

    // Assertions to verify the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.event_enrollment.create', $event->getType());
    $this->assertEquals('/node/add/event', $event->getSource());
    $this->assertEquals('9fe3acb4-9884-59dc-8317-9ae3acd64648', $event->getId());
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
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.create');

    $this->assertEquals('9fe3acb4-9884-59dc-8317-9ae3acd64648', $event->getId());
  }

  /**
   * Test generateEventId for delete event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.delete');

    $this->assertEquals('b52cc6ea-aa31-517e-b11b-04cfb68fc9fb', $event->getId());
  }

  /**
   * Test generateEventId for request.create event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdRequestCreate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.create');

    $this->assertEquals('396f2ee9-6b41-5184-8382-194296601157', $event->getId());
  }

  /**
   * Test generateEventId for request.delete event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdRequestDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.delete');

    $this->assertEquals('fd691dd7-463e-5f5f-8bef-33f705582f78', $event->getId());
  }

  /**
   * Test generateEventId for request.accept event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdRequestAccept(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.accept');

    $this->assertEquals('24d4a7f3-b2a0-5359-9175-63158d8cb525', $event->getId());
  }

  /**
   * Test generateEventId for request.decline event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdRequestDecline(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.request.decline');

    $this->assertEquals('e7aa9fc7-e616-55b7-91af-4a22eafca7f8', $event->getId());
  }

  /**
   * Test generateEventId for invite.create event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdInviteCreate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.create');

    $this->assertEquals('33345ebd-d16e-5ac4-8159-3e8070dac395', $event->getId());
  }

  /**
   * Test generateEventId for invite.delete event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdInviteDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.delete');

    $this->assertEquals('8ca87e4c-2922-5433-b963-fb15eca47be2', $event->getId());
  }

  /**
   * Test generateEventId for invite.accept event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdInviteAccept(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.accept');

    $this->assertEquals('ecf68658-117c-5b06-bc13-57d8542b734b', $event->getId());
  }

  /**
   * Test generateEventId for invite.decline event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdInviteDecline(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->eventEnrollment, 'com.getopensocial.cms.event_enrollment.invite.decline');

    $this->assertEquals('2036006e-0358-567b-81e6-a2c059464060', $event->getId());
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_event\EdaEventEnrollmentHandler
   *   The mocked handler instance.
   */
  protected function getMockedHandler(): EdaEventEnrollmentHandler {
    return new EdaEventEnrollmentHandler(
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
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
