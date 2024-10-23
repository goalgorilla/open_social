<?php

namespace Drupal\Tests\social_event\Unit;

use CloudEvents\V1\CloudEvent;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_eda_dispatcher\Dispatcher as SocialEdaDispatcher;
use Drupal\social_event\EdaHandler;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
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
  protected SocialEdaDispatcher $dispatcher;

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
  protected FieldItemListInterface $eventTypeField;

  /**
   * Represents a list of field items, such as a reference to groups.
   */
  protected FieldItemListInterface $fieldItemList;

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

    // Mock Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock->reveal());
    \Drupal::setContainer($container);

    // Mock dependencies.
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class)
      ->reveal();

    // Mock the SocialEdaDispatcher service.
    $this->dispatcher = $this->getMockBuilder(SocialEdaDispatcher::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $entityStorageMock = $this->prophesize(EntityStorageInterface::class);
    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('user')
      ->willReturn($entityStorageMock->reveal());
    $this->entityTypeManager = $entityTypeManagerMock->reveal();

    // Mock the AccountProxyInterface.
    $accountMock = $this->prophesize(AccountProxyInterface::class);
    $accountMock->id()->willReturn(1);
    $this->account = $accountMock->reveal();

    // Mock the RouteMatchInterface.
    $routeMatchMock = $this->prophesize(RouteMatchInterface::class);
    $routeMatchMock->getRouteName()->willReturn('entity.node.edit_form');
    $this->routeMatch = $routeMatchMock->reveal();

    // Resolve UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Resolve Request.
    $requestMock = $this->prophesize(Request::class);
    $requestMock->getUri()->willReturn('http://example.com/node/add/event');
    $requestMock->getPathInfo()->willReturn('/node/add/event');
    $this->request = $requestMock->reveal();

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Mock the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Mock the EntityInterface.
    $entityMock = $this->prophesize(EntityInterface::class);
    $entityMock->toUrl('canonical', ['absolute' => TRUE])
      ->willReturn($this->url);
    $entityMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $entityMock->label()->willReturn('Test Entity');
    $this->entityInterface = $entityMock->reveal();

    // Mock the UserInterface.
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $userMock->getDisplayName()->willReturn('User name');
    $userMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $this->userInterface = $userMock->reveal();

    // Mock Address field.
    $addressItemMock = $this->prophesize(AddressItem::class);
    $this->addressItem = $addressItemMock->reveal();

    $addressItemListMock = $this->prophesize(AddressFieldItemList::class);
    $addressItemListMock->first()->willReturn($this->addressItem);
    $this->addressItemList = $addressItemListMock->reveal();

    // Mock the field_event_type.
    $eventTypeTermMock = $this->prophesize(TermInterface::class);
    $eventTypeTermMock->label()->willReturn('Term Label');
    $this->eventTypeTerm = $eventTypeTermMock->reveal();

    $eventTypeFieldMock = $this->prophesize(FieldItemListInterface::class);
    $eventTypeFieldMock->isEmpty()->willReturn(FALSE);
    $eventTypeFieldMock->getEntity()->willReturn($this->eventTypeTerm);
    $this->eventTypeField = $eventTypeFieldMock->reveal();

    // Mock the FieldItemListInterface.
    $fieldItemListMock = $this->prophesize(FieldItemListInterface::class);
    $fieldItemListMock->isEmpty()->willReturn(FALSE);
    $fieldItemListMock->getEntity()->willReturn($this->entityInterface);
    $this->fieldItemList = $fieldItemListMock->reveal();

    // Mock the Node.
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
    $nodeMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $nodeMock->hasField('field_event_type')->willReturn(TRUE);
    $nodeMock->get('field_event_type')->willReturn($this->eventTypeField);
    $this->node = $nodeMock->reveal();
  }

  /**
   * Test method fromEntity().
   */
  public function testFromEntity(): void {
    // Create the handler instance.
    $handler = new EdaHandler(
      $this->dispatcher,
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch
    );

    // Create the event object.
    $event = $handler->fromEntity($this->node, 'com.getopensocial.cms.event.create');

    // Assert the result is an instance of CloudEvent.
    $this->assertInstanceOf(CloudEvent::class, $event);
  }

}
