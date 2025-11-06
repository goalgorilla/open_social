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
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
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
    $this->routeMatch->method('getRouteName')->willReturn('entity.post.canonical');

    // Mock the UUID.
    $this->uuid = $this->createMock(UuidInterface::class);
    $this->uuid->method('generate')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');

    // Mock the Request.
    $this->request = $this->createMock(Request::class);
    $this->request->method('getUri')->willReturn('http://example.com/post/1');
    $this->request->method('getPathInfo')->willReturn('/post/1');

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturn($this->request);

    // Mock the URL object.
    $this->url = $this->createMock(Url::class);
    $this->url->method('toString')->willReturn('http://example.com');

    // Mock the UserInterface.
    $this->userInterface = $this->createMock(UserInterface::class);
    $this->userInterface->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->userInterface->method('getDisplayName')->willReturn('User name');
    $this->userInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the GroupInterface.
    $this->groupInterface = $this->createMock(GroupInterface::class);
    $this->groupInterface->method('uuid')->willReturn('group-uuid-1234');
    $this->groupInterface->method('label')->willReturn('Test Group');
    $this->groupInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Post.
    $this->post = $this->createMock(PostInterface::class);
    $this->post->method('getCreatedTime')->willReturn(1692614400);
    $this->post->method('getChangedTime')->willReturn(1692618000);
    $this->post->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_visibility', 'field_recipient_group', 'field_recipient_user']);
    });
    $this->post->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      if ($field_name === 'status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_visibility') {
        return (object) ['value' => '1'];
      }
      if ($field_name === 'field_recipient_group') {
        return $this->createEmptyField();
      }
      if ($field_name === 'field_recipient_user') {
        return $this->createEmptyField();
      }
      if ($field_name === 'user_id') {
        return (object) ['entity' => $this->userInterface];
      }
      return NULL;
    });
    $this->post->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $this->post->method('getEntityTypeId')->willReturn('post');

    // Mock the CloudEvent class.
    $this->cloudEvent = $this->createMock(CloudEventInterface::class);

    // Initialize the time service.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_post')->willReturn($this->logger);
  }

  /**
   * Creates an empty field item list for testing.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\Core\Entity\EntityInterface>
   *   The empty field item list.
   */
  protected function createEmptyField(): EntityReferenceFieldItemListInterface {
    $fieldMock = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $fieldMock->method('isEmpty')->willReturn(TRUE);
    return $fieldMock;
  }

  /**
   * Creates a group field item list for testing.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\Core\Entity\EntityInterface>
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
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\Core\Entity\EntityInterface>
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
    $post = $this->createMock(PostInterface::class);
    $post->method('getCreatedTime')->willReturn(1692614400);
    $post->method('getChangedTime')->willReturn(1692618000);
    $post->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_visibility', 'field_recipient_group', 'field_recipient_user']);
    });
    $post->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      if ($field_name === 'status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_visibility') {
        return (object) ['value' => '1'];
      }
      if ($field_name === 'field_recipient_group') {
        return $this->createGroupField();
      }
      if ($field_name === 'field_recipient_user') {
        return $this->createEmptyField();
      }
      if ($field_name === 'user_id') {
        return (object) ['entity' => $this->userInterface];
      }
      return NULL;
    });
    $post->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $post->method('getEntityTypeId')->willReturn('post');

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
    $post = $this->createMock(PostInterface::class);
    $post->method('getCreatedTime')->willReturn(1692614400);
    $post->method('getChangedTime')->willReturn(1692618000);
    $post->method('hasField')->willReturnCallback(function ($field_name) {
      return in_array($field_name, ['field_visibility', 'field_recipient_group', 'field_recipient_user']);
    });
    $post->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'uuid') {
        return (object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af'];
      }
      if ($field_name === 'status') {
        return (object) ['value' => 1];
      }
      if ($field_name === 'field_visibility') {
        return (object) ['value' => '1'];
      }
      if ($field_name === 'field_recipient_group') {
        return $this->createEmptyField();
      }
      if ($field_name === 'field_recipient_user') {
        return $this->createUserField();
      }
      if ($field_name === 'user_id') {
        return (object) ['entity' => $this->userInterface];
      }
      return NULL;
    });
    $post->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $post->method('getEntityTypeId')->willReturn('post');

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
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('moduleExists')->with('social_eda')->willReturn(FALSE);

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
      $this->loggerFactory,
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
      $this->loggerFactory,
      // @phpstan-ignore-next-line
      $this->dispatcher
    );
  }

}
