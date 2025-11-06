<?php

namespace Drupal\Tests\social_group_flexible_group\Unit;

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
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_group_flexible_group\EdaHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_group_flexible_group\EdaHandler
 * @group social_group_flexible_group
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
   * Represents an address field item.
   */
  protected AddressItem $addressItem;

  /**
   * Represents a list of address field items.
   */
  protected AddressFieldItemList $addressItemList;

  /**
   * Represents an group type.
   */
  protected GroupTypeInterface $groupType;

  /**
   * Represents a list of field items, such as a reference to groups.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface>
   */
  protected FieldItemListInterface $fieldItemList;

  /**
   * Represents a group entity.
   */
  protected GroupInterface $group;

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
    $configMock = $this->createMock(ConfigInterface::class);
    $configMock->method('get')->with('namespace')->willReturn('com.getopensocial');

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->with('social_eda.settings')->willReturn($configMock);

    // Mock Drupal's container.
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

    // Mock the UUID.
    $this->uuid = $this->createMock(UuidInterface::class);
    $this->uuid->method('generate')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');

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
      ->with('canonical', ['absolute' => TRUE])
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

    // Mock Group Type.
    $this->groupType = $this->createMock(GroupType::class);
    $this->groupType->method('get')->with('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44ao');

    // Mock Address field.
    $this->addressItem = $this->createMock(AddressItem::class);
    $this->addressItemList = $this->createMock(AddressFieldItemList::class);
    $this->addressItemList->method('first')->willReturn($this->addressItem);

    // Mock the FieldItemListInterface.
    $this->fieldItemList = $this->createMock(FieldItemListInterface::class);
    $this->fieldItemList->method('isEmpty')->willReturn(FALSE);
    $this->fieldItemList->method('getEntity')->willReturn($this->entityInterface);

    // Mock the Group.
    $this->group = $this->createMock(GroupInterface::class);
    $this->group->method('label')->willReturn('Group Title');
    $this->group->method('getCreatedTime')->willReturn(1692614400);
    $this->group->method('getGroupType')->willReturn($this->groupType);
    $this->group->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_group_allowed_visibility', 'field_flexible_group_visibility']);
    });
    $this->group->method('getChangedTime')->willReturn(1692618000);
    $this->group->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      if ($field_name === 'status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_group_allowed_visibility') {
        return (object) ['value' => 'public'];
      }
      if ($field_name === 'field_flexible_group_visibility') {
        return (object) ['value' => 'public'];
      }
      if ($field_name === 'field_group_allowed_join_method') {
        return (object) ['value' => 'request'];
      }
      if ($field_name === 'field_group_address') {
        return $this->addressItemList;
      }
      if ($field_name === 'field_group_location') {
        return $this->addressItemList;
      }
      if ($field_name === 'uid') {
        return (object) ['entity' => $this->userInterface];
      }
      return NULL;
    });
    $this->group->method('toUrl')
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
    $this->loggerFactory->method('get')->with('social_group_flexible_group')->willReturn($this->logger);
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
    $event = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.group.create', $event->getType());
    $this->assertEquals('/node/add/event', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test the groupCreate() method.
   *
   * @covers ::groupCreate
   */
  public function testGroupCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the group object.
    $group = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group.v1'),
        $this->equalTo($group)
      );

    // Call the groupCreate method.
    $handler->groupCreate($this->group);

    // Assert that the correct group is dispatched.
    $this->assertEquals('com.getopensocial.cms.group.create', $group->getType());
  }

  /**
   * Test the groupUnpublish() method.
   *
   * @covers ::groupUnpublish
   */
  public function testGroupUnpublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the group object.
    $group = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.unpublish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group.v1'),
        $this->equalTo($group)
      );

    // Call the groupUnpublish method.
    $handler->groupUnpublish($this->group);

    // Assert that the correct group is dispatched.
    $this->assertEquals('com.getopensocial.cms.group.unpublish', $group->getType());
  }

  /**
   * Test the groupPublish() method.
   *
   * @covers ::groupPublish
   */
  public function testGroupPublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the group object.
    $group = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.publish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group.v1'),
        $this->equalTo($group)
      );

    // Call the groupPublish method.
    $handler->groupPublish($this->group);

    // Assert that the correct group is dispatched.
    $this->assertEquals('com.getopensocial.cms.group.publish', $group->getType());
  }

  /**
   * Test the groupUpdate() method.
   *
   * @covers ::groupUpdate
   */
  public function testGroupUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the group object.
    $group = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group.v1'),
        $this->equalTo($group)
      );

    // Call the groupUpdate method.
    $handler->groupUpdate($this->group);

    // Assert that the correct group is dispatched.
    $this->assertEquals('com.getopensocial.cms.group.update', $group->getType());
  }

  /**
   * Test the groupDelete() method.
   *
   * @covers ::groupDelete
   */
  public function testGroupDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the group object.
    $group = $handler->fromEntity($this->group, 'com.getopensocial.cms.group.delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group.v1'),
        $this->equalTo($group)
      );

    // Call the groupDelete method.
    $handler->groupDelete($this->group);

    // Assert that the correct group is dispatched.
    $this->assertEquals('com.getopensocial.cms.group.delete', $group->getType());
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_group_flexible_group\EdaHandler
   *   The mocked handler instance.
   */
  protected function getMockedHandler(): EdaHandler {
    return new EdaHandler(
      $this->dispatcher,
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
    );
  }

}
