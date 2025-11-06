<?php

namespace Drupal\Tests\social_comment\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\comment\CommentInterface;
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
use Drupal\social_comment\EdaHandler;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_comment\EdaHandler
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
   * Represents a node entity (commented entity).
   */
  protected NodeInterface $commentedNode;

  /**
   * Represents a comment entity.
   */
  protected CommentInterface $comment;

  /**
   * Represents an HTTP request.
   */
  protected Request $request;

  /**
   * Manages entity types and their storage handlers.
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

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $entityStorageMock = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('user')->willReturn($entityStorageMock);

    // Mock the AccountProxyInterface.
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->account->method('id')->willReturn(1);

    // Mock the RouteMatchInterface.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.comment.edit_form');

    // Mock the UUID.
    $this->uuid = $this->createMock(UuidInterface::class);
    $this->uuid->method('generate')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');

    // Mock the Request.
    $headersMock = $this->createMock(HeaderBag::class);
    $headersMock->method('get')->with('referer')->willReturn('http://example.com/stream');
    $this->request = $this->createMock(Request::class);
    $this->request->method('getUri')->willReturn('http://example.com/comment/1');
    $this->request->method('getPathInfo')->willReturn('/comment/1');
    $this->request->headers = $headersMock;

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturn($this->request);

    // Mock the URL object.
    $this->url = $this->createMock(Url::class);
    $this->url->method('toString')->willReturn('http://example.com');

    // Mock the EntityInterface (commented node).
    $this->commentedNode = $this->createMock(NodeInterface::class);
    $this->commentedNode->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);
    $this->commentedNode->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->commentedNode->method('label')->willReturn('Test Node');
    $this->commentedNode->method('getEntityTypeId')->willReturn('node');
    $this->commentedNode->method('bundle')->willReturn('topic');

    // Mock the UserInterface.
    $this->userInterface = $this->createMock(UserInterface::class);
    $this->userInterface->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->userInterface->method('getDisplayName')->willReturn('User name');
    $this->userInterface->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Comment.
    $this->comment = $this->createMock(CommentInterface::class);
    $this->comment->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->comment->method('getCreatedTime')->willReturn(1692614400);
    $this->comment->method('getChangedTime')->willReturn(1692618000);
    $this->comment->method('isPublished')->willReturn(TRUE);
    $this->comment->method('getOwner')->willReturn($this->userInterface);
    $this->comment->method('getCommentedEntity')->willReturn($this->commentedNode);
    $this->comment->method('getCommentedEntityTypeId')->willReturn('node');
    $this->comment->method('hasParentComment')->willReturn(FALSE);
    $this->comment->method('toUrl')
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
    $this->loggerFactory->method('get')->with('social_comment')->willReturn($this->logger);
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
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.comment.create', $event->getType());
    $this->assertEquals('/stream', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals(DateTime::fromTimestamp(1234567890)->toImmutableDateTime(), $event->getTime());
  }

  /**
   * Test the commentCreate() method.
   *
   * @covers ::commentCreate
   */
  public function testCommentCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.comment.v1'),
        $this->equalTo($event)
      );

    // Call the commentCreate method.
    $handler->commentCreate($this->comment);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.comment.create', $event->getType());
  }

  /**
   * Test the commentPublish() method.
   *
   * @covers ::commentPublish
   */
  public function testCommentPublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.publish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.comment.v1'),
        $this->equalTo($event)
      );

    // Call the commentPublish method.
    $handler->commentPublish($this->comment);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.comment.publish', $event->getType());
  }

  /**
   * Test the commentUnpublish() method.
   *
   * @covers ::commentUnpublish
   */
  public function testCommentUnpublish(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.unpublish');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.comment.v1'),
        $this->equalTo($event)
      );

    // Call the commentUnpublish method.
    $handler->commentUnpublish($this->comment);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.comment.unpublish', $event->getType());
  }

  /**
   * Test the commentUpdate() method.
   *
   * @covers ::commentUpdate
   */
  public function testCommentUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.comment.v1'),
        $this->equalTo($event)
      );

    // Call the commentUpdate method.
    $handler->commentUpdate($this->comment);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.comment.update', $event->getType());
  }

  /**
   * Test the commentDelete() method.
   *
   * @covers ::commentDelete
   */
  public function testCommentDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.delete', 'delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.comment.v1'),
        $this->equalTo($event)
      );

    // Call the commentDelete method.
    $handler->commentDelete($this->comment);
  }

  /**
   * Test thread calculation for top-level comment.
   *
   * @covers ::calculateThreadInfo
   */
  public function testThreadCalculationTopLevel(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->comment, 'com.getopensocial.cms.comment.create');

    // Get the data to verify thread information.
    $data = $event->getData();
    $thread = $data['comment']->thread;

    // For a top-level comment, root_id should be the comment's own ID.
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $thread->root_id);
    $this->assertNull($thread->parent_id);
    $this->assertEquals(0, $thread->depth);
  }

  /**
   * Test thread calculation for reply comment.
   *
   * @covers ::calculateThreadInfo
   */
  public function testThreadCalculationReply(): void {
    // Create a parent comment mock.
    $parentComment = $this->createMock(CommentInterface::class);
    $parentComment->method('uuid')->willReturn('parent-comment-uuid');
    $parentComment->method('hasParentComment')->willReturn(FALSE);
    $parentComment->method('getParentComment')->willReturn(NULL);
    $parentComment->method('getEntityTypeId')->willReturn('comment');
    $parentComment->method('getCommentedEntity')->willReturn($this->commentedNode);
    $parentComment->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Update the comment mock to have a parent.
    $replyComment = $this->createMock(CommentInterface::class);
    $replyComment->method('uuid')->willReturn('reply-comment-uuid');
    $replyComment->method('getCreatedTime')->willReturn(1692614400);
    $replyComment->method('getChangedTime')->willReturn(1692618000);
    $replyComment->method('isPublished')->willReturn(TRUE);
    $replyComment->method('getOwner')->willReturn($this->userInterface);
    $replyComment->method('getCommentedEntity')->willReturn($parentComment);
    $replyComment->method('getCommentedEntityTypeId')->willReturn('comment');
    $replyComment->method('hasParentComment')->willReturn(TRUE);
    $replyComment->method('getParentComment')->willReturn($parentComment);
    $replyComment->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($replyComment, 'com.getopensocial.cms.comment.create');

    // Get the data to verify thread information.
    $data = $event->getData();
    $thread = $data['comment']->thread;

    // For a reply comment, root_id should be the parent's ID, parent_id should
    // be the parent's ID.
    $this->assertEquals('parent-comment-uuid', $thread->root_id);
    $this->assertEquals('parent-comment-uuid', $thread->parent_id);
    $this->assertEquals(1, $thread->depth);
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_comment\EdaHandler
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
      $this->dispatcher,
    );
  }

}
