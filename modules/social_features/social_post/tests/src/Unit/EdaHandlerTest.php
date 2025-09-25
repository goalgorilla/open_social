<?php

namespace Drupal\Tests\social_post\Unit;

use CloudEvents\V1\CloudEventInterface;
use Consolidation\Config\ConfigInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\social_post\EdaHandler;
use Drupal\social_post\Entity\PostInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_post\EdaHandler
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
   * Represents a user entity.
   */
  protected UserInterface $userInterface;

  /**
   * Represents a group entity.
   */
  protected GroupInterface $groupInterface;

  /**
   * Represents a post entity.
   */
  protected PostInterface $post;

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
    $routeMatchMock->getRouteName()->willReturn('entity.post.canonical');
    $this->routeMatch = $routeMatchMock->reveal();

    // Prophesize the UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Prophesize the Request.
    $requestMock = $this->prophesize(Request::class);
    $requestMock->getUri()->willReturn('http://example.com/post/1');
    $requestMock->getPathInfo()->willReturn('/post/1');
    $this->request = $requestMock->reveal();

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Prophesize the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Prophesize the UserInterface.
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $userMock->getDisplayName()->willReturn('User name');
    $userMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $this->userInterface = $userMock->reveal();

    // Prophesize the GroupInterface.
    $groupMock = $this->prophesize(GroupInterface::class);
    $groupMock->uuid()->willReturn('group-uuid-1234');
    $groupMock->label()->willReturn('Test Group');
    $groupMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $this->groupInterface = $groupMock->reveal();

    // Prophesize the Post.
    $postMock = $this->prophesize(PostInterface::class);
    $postMock->getCreatedTime()->willReturn(1692614400);
    $postMock->getChangedTime()->willReturn(1692618000);
    $postMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $postMock->get('status')->willReturn((object) ['value' => 1]);
    $postMock->get('field_visibility')
      ->willReturn((object) ['value' => '1']);
    $postMock->hasField('field_visibility')->willReturn(TRUE);
    $postMock->hasField('field_recipient_group')->willReturn(TRUE);
    $postMock->hasField('field_recipient_user')->willReturn(TRUE);
    $postMock->get('field_recipient_group')->willReturn($this->createEmptyField());
    $postMock->get('field_recipient_user')->willReturn($this->createEmptyField());
    $postMock->get('user_id')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $postMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $postMock->getEntityTypeId()->willReturn('post');
    $this->post = $postMock->reveal();

    // Prophesize the CloudEvent class.
    $cloudEventMock = $this->prophesize(CloudEventInterface::class);
    $this->cloudEvent = $cloudEventMock->reveal();

    // Initialize the time service.
    $timeMock = $this->prophesize(TimeInterface::class);
    $timeMock->getRequestTime()->willReturn(1234567890);
    $this->time = $timeMock->reveal();
  }

  /**
   * Creates an empty field item list for testing.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   *   The empty field item list.
   */
  protected function createEmptyField(): EntityReferenceFieldItemListInterface {
    $fieldMock = $this->prophesize(EntityReferenceFieldItemListInterface::class);
    $fieldMock->isEmpty()->willReturn(TRUE);
    return $fieldMock->reveal();
  }

  /**
   * Creates a group field item list for testing.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   *   The group field item list.
   */
  protected function createGroupField(): EntityReferenceFieldItemListInterface {
    $fieldMock = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $fieldMock->method('isEmpty')->willReturn(FALSE);
    $fieldMock->method('getEntity')->willReturn($this->groupInterface);
    $fieldMock->method('__get')->with('entity')->willReturn($this->groupInterface);
    return $fieldMock;
  }

  /**
   * Creates a user field item list for testing.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   *   The user field item list.
   */
  protected function createUserField(): EntityReferenceFieldItemListInterface {
    $fieldMock = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $fieldMock->method('isEmpty')->willReturn(FALSE);
    $fieldMock->method('getEntity')->willReturn($this->userInterface);
    $fieldMock->method('__get')->with('entity')->willReturn($this->userInterface);
    return $fieldMock;
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
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.post.create', $event->getType());
    $this->assertEquals('/post/1', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test the postCreate() method.
   *
   * @covers ::postCreate
   */
  public function testPostCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.post.v1'),
        $this->equalTo($event)
      );

    // Call the postCreate method.
    $handler->postCreate($this->post);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.post.create', $event->getType());
  }

  /**
   * Test the postPublish() method.
   *
   * @covers ::postPublish
   */
  public function testPostPublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.publish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.post.v1'),
        $this->equalTo($event)
      );

    // Call the postPublish method.
    $handler->postPublish($this->post);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.post.publish', $event->getType());
  }

  /**
   * Test the postUnpublish() method.
   *
   * @covers ::postUnpublish
   */
  public function testPostUnpublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.unpublish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.post.v1'),
        $this->equalTo($event)
      );

    // Call the postUnpublish method.
    $handler->postUnpublish($this->post);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.post.unpublish', $event->getType());
  }

  /**
   * Test the postUpdate() method.
   *
   * @covers ::postUpdate
   */
  public function testPostUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.post.v1'),
        $this->equalTo($event)
      );

    // Call the postUpdate method.
    $handler->postUpdate($this->post);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.post.update', $event->getType());
  }

  /**
   * Test the postDelete() method.
   *
   * @covers ::postDelete
   */
  public function testPostDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->post, 'com.getopensocial.cms.post.delete', 'delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.post.v1'),
        $this->equalTo($event)
      );

    // Call the postDelete method.
    $handler->postDelete($this->post);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.post.delete', $event->getType());
  }

  /**
   * Test the fromEntity() method with group stream.
   *
   * @covers ::fromEntity
   */
  public function testFromEntityGroupStream(): void {
    // Create a post with group recipient.
    $postMock = $this->prophesize(PostInterface::class);
    $postMock->getCreatedTime()->willReturn(1692614400);
    $postMock->getChangedTime()->willReturn(1692618000);
    $postMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $postMock->get('status')->willReturn((object) ['value' => 1]);
    $postMock->get('field_visibility')
      ->willReturn((object) ['value' => '1']);
    $postMock->hasField('field_visibility')->willReturn(TRUE);
    $postMock->hasField('field_recipient_group')->willReturn(TRUE);
    $postMock->hasField('field_recipient_user')->willReturn(TRUE);
    $postMock->get('field_recipient_group')->willReturn($this->createGroupField());
    $postMock->get('field_recipient_user')->willReturn($this->createEmptyField());
    $postMock->get('user_id')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $postMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $postMock->getEntityTypeId()->willReturn('post');
    $post = $postMock->reveal();

    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($post, 'com.getopensocial.cms.post.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.post.create', $event->getType());
    $this->assertEquals('/post/1', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
  }

  /**
   * Test the fromEntity() method with user stream.
   *
   * @covers ::fromEntity
   */
  public function testFromEntityUserStream(): void {
    // Create a post with user recipient.
    $postMock = $this->prophesize(PostInterface::class);
    $postMock->getCreatedTime()->willReturn(1692614400);
    $postMock->getChangedTime()->willReturn(1692618000);
    $postMock->get('uuid')
      ->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $postMock->get('status')->willReturn((object) ['value' => 1]);
    $postMock->get('field_visibility')
      ->willReturn((object) ['value' => '1']);
    $postMock->hasField('field_visibility')->willReturn(TRUE);
    $postMock->hasField('field_recipient_group')->willReturn(TRUE);
    $postMock->hasField('field_recipient_user')->willReturn(TRUE);
    $postMock->get('field_recipient_group')->willReturn($this->createEmptyField());
    $postMock->get('field_recipient_user')->willReturn($this->createUserField());
    $postMock->get('user_id')
      ->willReturn((object) ['entity' => $this->userInterface]);
    $postMock->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->willReturn($this->url);
    $postMock->getEntityTypeId()->willReturn('post');
    $post = $postMock->reveal();

    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($post, 'com.getopensocial.cms.post.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.post.create', $event->getType());
    $this->assertEquals('/post/1', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
  }

  /**
   * Test that dispatch is skipped when module is not enabled.
   *
   * @covers ::postCreate
   */
  public function testDispatchSkippedWhenModuleNotEnabled(): void {
    // Mock module handler to return FALSE for social_eda.
    $moduleHandlerMock = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandlerMock->moduleExists('social_eda')->willReturn(FALSE);
    $moduleHandler = $moduleHandlerMock->reveal();

    // Create the handler instance with disabled module.
    $handler = new EdaHandler(
      $this->uuid,
      $this->requestStack,
      $moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      // @phpstan-ignore-next-line
      $this->dispatcher
    );

    // Expect the dispatcher NOT to be called.
    $this->dispatcher->expects($this->never())
      ->method('dispatch');

    // Call the postCreate method.
    $handler->postCreate($this->post);
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_post\EdaHandler
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
