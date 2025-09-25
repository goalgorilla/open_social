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
use PHPUnit\Framework\MockObject\MockObject;
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
   * Represents an group type.
   */
  protected GroupTypeInterface $groupType;

  /**
   * Represents a list of field items, such as a reference to groups.
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
    $entityMock->toUrl('canonical', ['absolute' => TRUE])
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

    // Mock Group Type.
    $groupTypeMock = $this->prophesize(GroupType::class);
    $groupTypeMock->get('uuid')
      ->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44ao');
    $this->groupType = $groupTypeMock->reveal();

    // Mock Address field.
    $addressItemMock = $this->prophesize(AddressItem::class);
    $this->addressItem = $addressItemMock->reveal();

    $addressItemListMock = $this->prophesize(AddressFieldItemList::class);
    $addressItemListMock->first()->willReturn($this->addressItem);
    $this->addressItemList = $addressItemListMock->reveal();

    // Prophesize the FieldItemListInterface.
    $fieldItemListMock = $this->prophesize(FieldItemListInterface::class);
    $fieldItemListMock->isEmpty()->willReturn(FALSE);
    $fieldItemListMock->getEntity()->willReturn($this->entityInterface);
    $this->fieldItemList = $fieldItemListMock->reveal();

    // Prophesize the Group.
    $groupMock = $this->prophesize(GroupInterface::class);
    $groupMock->label()->willReturn('Group Title');
    $groupMock->getCreatedTime()->willReturn(1692614400);
    $groupMock->getGroupType()->willReturn($this->groupType);
    $groupMock->hasField('field_group_allowed_visibility')->willReturn(TRUE);
    $groupMock->hasField('field_flexible_group_visibility')->willReturn(TRUE);
    $groupMock->getChangedTime()->willReturn(1692618000);
    $groupMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $groupMock->get('status')->willReturn((object) ['value' => 1]);
    $groupMock->get('field_group_allowed_visibility')
      ->willReturn((object) ['value' => 'public']);
    $groupMock->get('field_flexible_group_visibility')
      ->willReturn((object) ['value' => 'public']);
    $groupMock->get('field_group_allowed_join_method')->willReturn((object) ['value' => 'request']);
    $groupMock->get('field_group_address')->willReturn($this->addressItemList);
    $groupMock->get('field_group_location')->willReturn($this->addressItemList);
    $groupMock->get('uid')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $groupMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $this->group = $groupMock->reveal();

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
      // @phpstan-ignore-next-line
      $this->dispatcher,
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
    );
  }

}
