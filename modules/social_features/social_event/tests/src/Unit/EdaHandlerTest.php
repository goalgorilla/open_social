<?php

namespace Drupal\Tests\social_event\Unit;

use CloudEvents\V1\CloudEventInterface;
use Consolidation\Config\ConfigInterface;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   */
  protected EntityReferenceFieldItemListInterface $eventTypeField;

  /**
   * Represents a list of field items, such as a reference to groups.
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
    $configMock = $this->prophesize(ConfigInterface::class);
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
    $this->dispatcher = $this->getMockBuilder(DispatcherInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Prophesize the EntityTypeManagerInterface and the corresponding storage.
    $entityStorageMock = $this->prophesize(EntityStorageInterface::class);
    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('user')
      ->willReturn($entityStorageMock->reveal());
    $this->entityTypeManager = $entityTypeManagerMock->reveal();

    // Prophesize the AccountProxyInterface.
    $accountMock = $this->prophesize(AccountProxyInterface::class);
    $accountMock->id()->willReturn(1);
    $this->account = $accountMock->reveal();

    // Prophesize the RouteMatchInterface.
    $routeMatchMock = $this->prophesize(RouteMatchInterface::class);
    $routeMatchMock->getRouteName()->willReturn('entity.node.edit_form');
    $this->routeMatch = $routeMatchMock->reveal();

    // Prophesize the UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Prophesize the Request.
    $requestMock = $this->prophesize(Request::class);
    $requestMock->getUri()->willReturn('http://example.com/node/add/event');
    $requestMock->getPathInfo()->willReturn('/node/add/event');
    $this->request = $requestMock->reveal();

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Prophesize the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Prophesize the EntityInterface.
    $entityMock = $this->prophesize(EntityInterface::class);
    $entityMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $entityMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $entityMock->label()->willReturn('Test Entity');
    $this->entityInterface = $entityMock->reveal();

    // Prophesize the UserInterface.
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $userMock->getDisplayName()->willReturn('User name');
    $userMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $this->userInterface = $userMock->reveal();

    // Mock Address field.
    $addressItemMock = $this->prophesize(AddressItem::class);
    $this->addressItem = $addressItemMock->reveal();

    $addressItemListMock = $this->prophesize(AddressFieldItemList::class);
    $addressItemListMock->first()->willReturn($this->addressItem);
    $this->addressItemList = $addressItemListMock->reveal();

    // Prophesize the field_event_type.
    $eventTypeTermMock = $this->prophesize(TermInterface::class);
    $eventTypeTermMock->label()->willReturn('Term Label');
    $this->eventTypeTerm = $eventTypeTermMock->reveal();

    $eventTypeFieldMock = $this->prophesize(EntityReferenceFieldItemListInterface::class);
    $eventTypeFieldMock->isEmpty()->willReturn(FALSE);
    $eventTypeFieldMock->getEntity()->willReturn($this->eventTypeTerm);
    $eventTypeFieldMock->referencedEntities()->willReturn([$this->eventTypeTerm]);
    $this->eventTypeField = $eventTypeFieldMock->reveal();

    // Prophesize the FieldItemListInterface.
    $fieldItemListMock = $this->prophesize(EntityReferenceFieldItemListInterface::class);
    $fieldItemListMock->isEmpty()->willReturn(FALSE);
    $fieldItemListMock->getEntity()->willReturn($this->entityInterface);
    $fieldItemListMock->referencedEntities()->willReturn([$this->entityInterface]);
    $this->fieldItemList = $fieldItemListMock->reveal();

    // Prophesize the Node.
    $nodeMock = $this->prophesize(NodeInterface::class);
    $nodeMock->label()->willReturn('Event Title');
    $nodeMock->getCreatedTime()->willReturn(1692614400);
    $nodeMock->hasField('field_content_visibility')->willReturn(TRUE);
    $nodeMock->hasField('groups')->willReturn(TRUE);
    $nodeMock->getChangedTime()->willReturn(1692618000);
    $nodeMock->get('groups')->willReturn($this->fieldItemList);
    $nodeMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $nodeMock->get('status')->willReturn((object) ['value' => 1]);
    $nodeMock->get('field_content_visibility')
      ->willReturn((object) ['value' => 'public']);
    $nodeMock->get('field_event_all_day')->willReturn((object) ['value' => 1]);
    $nodeMock->get('field_event_date')
      ->willReturn((object) ['value' => '2024-08-21T10:00:00']);
    $nodeMock->get('field_event_date_end')
      ->willReturn((object) ['value' => '2024-08-21T10:00:00']);
    $nodeMock->get('field_event_address')->willReturn($this->addressItemList);
    $nodeMock->get('field_event_location')
      ->willReturn((object) ['value' => 'Location Label']);
    $nodeMock->get('field_event_enroll')->willReturn((object) ['value' => 1]);
    $nodeMock->get('field_enroll_method')->willReturn((object) ['value' => 0]);
    $nodeMock->get('field_event_type')->willReturn((object) ['value' => 0]);
    $nodeMock->get('uid')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $nodeMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $nodeMock->hasField('field_event_type')->willReturn(TRUE);
    $nodeMock->get('field_event_type')->willReturn($this->eventTypeField);
    $this->node = $nodeMock->reveal();

    // Prophesize the CloudEvent class.
    $cloudEventMock = $this->prophesize(CloudEventInterface::class);
    $this->cloudEvent = $cloudEventMock->reveal();

    // Initialize the time service.
    $timeMock = $this->prophesize(TimeInterface::class);
    $timeMock->getRequestTime()->willReturn(1234567890);
    $this->time = $timeMock->reveal();
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
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
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
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      // @phpstan-ignore-next-line
      $this->dispatcher
    );
  }

}
