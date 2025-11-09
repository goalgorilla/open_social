<?php

namespace Drupal\Tests\social_follow_user\Unit;

use CloudEvents\V1\CloudEventInterface;
use Drupal\Component\Datetime\TimeInterface;
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
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\flag\FlaggingInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_follow_user\EdaHandler;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_eda\Types\DateTime;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_follow_user\EdaHandler
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
   * Represents a user entity (target user being followed).
   */
  protected UserInterface $targetUser;

  /**
   * Represents a user entity (follower user).
   */
  protected UserInterface $followerUser;

  /**
   * Represents a profile entity.
   */
  protected ProfileInterface $profile;

  /**
   * Represents a flagging entity (follow relationship).
   */
  protected FlaggingInterface $flagging;

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

    // Mock the AccountProxyInterface.
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->account->method('id')->willReturn(1);

    // Mock the RouteMatchInterface.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.profile.canonical');

    // Create a real Symfony Request instance.
    $this->request = Request::create(
      'http://example.com/profile/1',
      'GET',
      [],
      [],
      [],
      ['HTTP_REFERER' => 'http://example.com/profile/1']
    );

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

    // Mock the target user (being followed).
    $this->targetUser = $this->createMock(UserInterface::class);
    $this->targetUser->method('uuid')->willReturn('target-user-uuid-123');
    $this->targetUser->method('getDisplayName')->willReturn('Target User');
    $this->targetUser->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the follower user.
    $this->followerUser = $this->createMock(UserInterface::class);
    $this->followerUser->method('uuid')->willReturn('follower-user-uuid-456');
    $this->followerUser->method('getDisplayName')->willReturn('Follower User');
    $this->followerUser->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Profile.
    $this->profile = $this->createMock(ProfileInterface::class);
    $this->profile->method('getOwner')->willReturn($this->targetUser);
    $this->profile->method('uuid')->willReturn('profile-uuid-789');
    $this->profile->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the Flagging (follow relationship).
    $this->flagging = $this->createMock(FlaggingInterface::class);
    $this->flagging->method('uuid')->willReturn('flagging-uuid-abc');
    $this->flagging->method('getCreatedTime')->willReturn(1692614400);
    $this->flagging->method('getFlagId')->willReturn('follow_user');
    $this->flagging->method('getOwner')->willReturn($this->followerUser);
    $this->flagging->method('getFlaggable')->willReturn($this->profile);

    // Mock the EntityTypeManagerInterface and the corresponding storage.
    $userStorageMock = $this->createMock(EntityStorageInterface::class);
    $userStorageMock->method('load')->with(1)->willReturn($this->targetUser);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('user')->willReturn($userStorageMock);

    // Mock the CloudEvent class.
    $this->cloudEvent = $this->createMock(CloudEventInterface::class);

    // Initialize the time service.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_follow_user')->willReturn($this->logger);
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
    $event = $handler->fromEntity($this->flagging, 'com.getopensocial.follow.user.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.follow.user.create', $event->getType());
    $this->assertEquals('/profile/1', $event->getSource());
    $this->assertEquals('98d3c199-7e0b-5d58-9cf3-7aa1506f28c8', $event->getId());
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
    $event = $handler->fromEntity($this->flagging, 'com.getopensocial.follow.user.create');

    $this->assertEquals('98d3c199-7e0b-5d58-9cf3-7aa1506f28c8', $event->getId());
  }

  /**
   * Test generateEventId for delete event.
   *
   * @covers ::fromEntity
   * @covers ::generateEventId
   */
  public function testGenerateEventIdDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->flagging, 'com.getopensocial.follow.user.delete');

    $this->assertEquals('188911c7-f35a-5429-8dd5-45b841001001', $event->getId());
  }

  /**
   * Test the followUserCreate() method.
   *
   * @covers ::followUserCreate
   */
  public function testFollowUserCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Expect the dispatch method in the dispatcher to be called with correct
    // topic and event type.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.follow.v1'),
        $this->callback(function ($event) {
          return $event->getType() === 'com.getopensocial.follow.user.create';
        })
      );

    // Call the followUserCreate method.
    $handler->followUserCreate($this->flagging);

    // Assert that the correct event type is dispatched.
    $this->assertEquals('com.getopensocial.follow.user.create', $handler->fromEntity($this->flagging, 'com.getopensocial.follow.user.create')->getType());
  }

  /**
   * Test the followUserDelete() method.
   *
   * @covers ::followUserDelete
   */
  public function testFollowUserDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Expect the dispatch method in the dispatcher to be called with correct
    // topic and event type.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.follow.v1'),
        $this->callback(function ($event) {
          return $event->getType() === 'com.getopensocial.follow.user.delete';
        })
      );

    // Call the followUserDelete method.
    $handler->followUserDelete($this->flagging);

    // Assert that the correct event type is dispatched.
    $this->assertEquals('com.getopensocial.follow.user.delete', $handler->fromEntity($this->flagging, 'com.getopensocial.follow.user.delete')->getType());
  }

  /**
   * Test that events are not dispatched when social_eda module is disabled.
   *
   * @covers ::followUserCreate
   */
  public function testNoDispatchWhenModuleDisabled(): void {
    // Create a new handler with module disabled.
    $moduleHandlerMock = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandlerMock->method('moduleExists')->with('social_eda')->willReturn(FALSE);

    $handler = new EdaHandler(
      $this->requestStack,
      $moduleHandlerMock,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher
    );

    // Expect dispatcher NOT to be called.
    $this->dispatcher->expects($this->never())
      ->method('dispatch');

    // Call the method.
    $handler->followUserCreate($this->flagging);
  }

  /**
   * Test that events are not dispatched when dispatcher is NULL.
   *
   * @covers ::followUserCreate
   */
  public function testNoDispatchWhenDispatcherIsNull(): void {
    // Create handler without dispatcher.
    $handler = new EdaHandler(
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      NULL
    );

    // Call the method - should not throw any exceptions.
    $handler->followUserCreate($this->flagging);
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_follow_user\EdaHandler
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
      $this->dispatcher,
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
