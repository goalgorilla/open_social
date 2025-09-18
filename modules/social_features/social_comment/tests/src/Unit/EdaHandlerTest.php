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
    $configMock = $this->prophesize(ImmutableConfig::class);
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
    $routeMatchMock->getRouteName()->willReturn('entity.comment.edit_form');
    $this->routeMatch = $routeMatchMock->reveal();

    // Prophesize the UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Prophesize the Request.
    $requestMock = $this->prophesize(Request::class);
    $requestMock->getUri()->willReturn('http://example.com/comment/1');
    $requestMock->getPathInfo()->willReturn('/comment/1');

    // Mock the headers to return a referrer.
    $headersMock = $this->prophesize(HeaderBag::class);
    $headersMock->get('referer')->willReturn('http://example.com/stream');
    $requestMock->headers = $headersMock->reveal();

    $this->request = $requestMock->reveal();

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Prophesize the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Prophesize the EntityInterface (commented node).
    $entityMock = $this->prophesize(NodeInterface::class);
    $entityMock->toUrl('canonical', ['absolute' => TRUE])
      ->willReturn($this->url);
    $entityMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $entityMock->label()->willReturn('Test Node');
    $entityMock->getEntityTypeId()->willReturn('node');
    $entityMock->bundle()->willReturn('topic');
    $this->commentedNode = $entityMock->reveal();

    // Prophesize the UserInterface.
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $userMock->getDisplayName()->willReturn('User name');
    $userMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $this->userInterface = $userMock->reveal();

    // Prophesize the Comment.
    $commentMock = $this->prophesize(CommentInterface::class);
    $commentMock->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $commentMock->getCreatedTime()->willReturn(1692614400);
    $commentMock->getChangedTime()->willReturn(1692618000);
    $commentMock->isPublished()->willReturn(TRUE);
    $commentMock->getOwner()->willReturn($this->userInterface);
    $commentMock->getCommentedEntity()->willReturn($this->commentedNode);
    $commentMock->getCommentedEntityTypeId()->willReturn('node');
    $commentMock->hasParentComment()->willReturn(FALSE);
    $commentMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $this->comment = $commentMock->reveal();

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
    $parentCommentMock = $this->prophesize(CommentInterface::class);
    $parentCommentMock->uuid()->willReturn('parent-comment-uuid');
    $parentCommentMock->hasParentComment()->willReturn(FALSE);
    $parentCommentMock->getParentComment()->willReturn(NULL);
    $parentCommentMock->getEntityTypeId()->willReturn('comment');
    $parentCommentMock->getCommentedEntity()->willReturn($this->nodeInterface);
    $parentCommentMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $parentComment = $parentCommentMock->reveal();

    // Update the comment mock to have a parent.
    $commentMock = $this->prophesize(CommentInterface::class);
    $commentMock->uuid()->willReturn('reply-comment-uuid');
    $commentMock->getCreatedTime()->willReturn(1692614400);
    $commentMock->getChangedTime()->willReturn(1692618000);
    $commentMock->isPublished()->willReturn(TRUE);
    $commentMock->getOwner()->willReturn($this->userInterface);
    $commentMock->getCommentedEntity()->willReturn($parentComment);
    $commentMock->getCommentedEntityTypeId()->willReturn('comment');
    $commentMock->hasParentComment()->willReturn(TRUE);
    $commentMock->getParentComment()->willReturn($parentComment);
    $commentMock->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $replyComment = $commentMock->reveal();

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
      // @phpstan-ignore-next-line
      $this->dispatcher,
    );
  }

}
