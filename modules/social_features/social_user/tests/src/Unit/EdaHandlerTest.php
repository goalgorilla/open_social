<?php

namespace Drupal\Tests\social_user\Unit;

use Consolidation\Config\ConfigInterface;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
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
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_user\EdaHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @coversDefaultClass \Drupal\social_user\EdaHandler
 */
class EdaHandlerTest extends UnitTestCase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Mocked dispatcher service for sending CloudEvents.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\social_eda\DispatcherInterface
   */
  protected $dispatcher;

  /**
   * The HTTP request stack service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account proxy service, representing the current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The address field item mock.
   *
   * @var \Drupal\address\Plugin\Field\FieldType\AddressItem
   */
  protected $addressItem;

  /**
   * The list of address field items mock.
   *
   * @var \Drupal\address\Plugin\Field\FieldType\AddressFieldItemList
   */
  protected $addressItemList;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The URL.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The profile entity.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * Represents the ConfigFactoryInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The logger channel factory.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Set up the test environment.
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
    $configSocialEdaMock = $this->createMock(ConfigInterface::class);
    $configSocialEdaMock->method('get')->with('namespace')->willReturn('com.getopensocial');

    // Mock the configuration for `user.settings.register`.
    $configUserSettingsMock = $this->createMock(ConfigInterface::class);
    $configUserSettingsMock->method('get')->with('register')->willReturn('visitors_admin_approval');

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')->willReturnCallback(function ($config_name) use ($configSocialEdaMock, $configUserSettingsMock) {
      if ($config_name === 'social_eda.settings') {
        return $configSocialEdaMock;
      }
      if ($config_name === 'user.settings') {
        return $configUserSettingsMock;
      }
      return NULL;
    });

    // Set up Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock);
    \Drupal::setContainer($container);

    // Mock the module handler and ensure `social_eda` is enabled.
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->method('moduleExists')->willReturnCallback(function ($module) {
      return in_array($module, ['social_eda', 'social_eda_dispatcher']);
    });

    // Mock the Dispatcher service.
    $this->dispatcher = $this->createMock(DispatcherInterface::class);

    // Mock the URL object.
    $this->url = $this->createMock(Url::class);
    $this->url->method('toString')->willReturn('http://example.com');

    // Mock the Address field.
    $this->addressItem = $this->createMock(AddressItem::class);
    $this->addressItemList = $this->createMock(AddressFieldItemList::class);
    $this->addressItemList->method('first')->willReturn($this->addressItem);

    // Mock the Profile entity.
    $this->profile = $this->createMock(ProfileInterface::class);
    $this->profile->method('get')->willReturnCallback(function ($field_name) {
      if ($field_name === 'field_profile_first_name') {
        return (object) ['value' => 'First'];
      }
      if ($field_name === 'field_profile_last_name') {
        return (object) ['value' => 'Last'];
      }
      if ($field_name === 'field_profile_phone_number') {
        return (object) ['value' => '123456789'];
      }
      if ($field_name === 'field_profile_function') {
        return (object) ['value' => 'Developer'];
      }
      if ($field_name === 'field_profile_organization') {
        return (object) ['value' => 'Organization'];
      }
      if ($field_name === 'field_profile_address') {
        return $this->addressItemList;
      }
      return NULL;
    });

    // Mock the affiliation data.
    $this->profile->method('getPrimaryAffiliationFunction')->willReturn('Developer');
    $this->profile->method('getPrimaryAffiliationName')->willReturn('Organization');

    // Mock the User entity.
    $this->user = $this->createMock(UserInterface::class);
    $this->user->method('get')->with('uuid')->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $this->user->method('uuid')->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->user->method('id')->willReturn(1);
    $this->user->method('getCreatedTime')->willReturn(1692614400);
    $this->user->method('getChangedTime')->willReturn(1692618000);
    $this->user->method('getLastLoginTime')->willReturn(1692618000);
    $this->user->method('isActive')->willReturn(TRUE);
    $this->user->method('getDisplayName')->willReturn('User Name');
    $this->user->method('getEmail')->willReturn('user@example.com');
    $this->user->method('getRoles')->willReturn(['authenticated']);
    $this->user->method('getPreferredLangcode')->willReturn('en');
    $this->user->method('getTimeZone')->willReturn('UTC');
    $this->user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->url);

    // Mock the EntityTypeManagerInterface and ProfileStorageInterface.
    $profileStorage = $this->createMock(ProfileStorageInterface::class);
    $profileStorage->method('loadByProperties')->willReturn([$this->profile]);
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')->with(1)->willReturn($this->user);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->willReturnCallback(function ($entity_type) use ($profileStorage, $userStorage) {
      if ($entity_type === 'profile') {
        return $profileStorage;
      }
      if ($entity_type === 'user') {
        return $userStorage;
      }
      return NULL;
    });

    // Mock the AccountProxyInterface.
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->account->method('id')->willReturn(1);

    // Mock the RouteMatchInterface.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.edit_form');

    // Mock the Request and RequestStack.
    $this->request = $this->createMock(Request::class);
    $this->request->method('getUri')->willReturn('http://example.com/user/register');
    $this->request->method('getPathInfo')->willReturn('/user/register');
    // Mock session for login/logout events (returns empty string to use last
    // login time fallback).
    $session = $this->createMock(SessionInterface::class);
    $session->method('getId')->willReturn('');
    // getSession() can return null, so we'll make it return the session mock.
    $this->request->method('getSession')->willReturn($session);

    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack->method('getCurrentRequest')->willReturn($this->request);

    // Initialize the time service.
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(1234567890);

    // Initialize the logger.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->with('social_user')->willReturn($this->logger);
  }

  /**
   * Test method fromEntity().
   *
   * @covers ::fromEntity
   */
  public function testFromEntity(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Test the fromEntity method.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.create');

    // Check that the event has expected attributes.
    $this->assertEquals('1.0', $event->getSpecVersion());
    $this->assertEquals('com.getopensocial.cms.user.create', $event->getType());
    $this->assertEquals('/user/register', $event->getSource());
    $this->assertEquals('ac632030-e33b-50d6-9bd1-3436127a2cd1', $event->getId());
  }

  /**
   * Test generateEventId for create event.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdCreate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.create');

    $this->assertEquals('ac632030-e33b-50d6-9bd1-3436127a2cd1', $event->getId());
  }

  /**
   * Test generateEventId for delete event.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdDelete(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.delete');

    $this->assertEquals('7f20bf8c-1b9e-5fe5-97b7-3318bad7de32', $event->getId());
  }

  /**
   * Test generateEventId for login event with session ID.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdLogin(): void {
    // Mock session with a session ID.
    $session = $this->createMock(SessionInterface::class);
    $session->method('getId')->willReturn('test-session-id-12345');

    // Create a new request with the session.
    $request = $this->createMock(Request::class);
    $request->method('getUri')->willReturn('http://example.com/user/register');
    $request->method('getPathInfo')->willReturn('/user/register');
    $request->method('getSession')->willReturn($session);

    // Create a new request stack with the request.
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($request);

    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\RequestStack $requestStack */
    $handler = new EdaHandler(
      $this->dispatcher,
      $requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
    );

    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.login');

    $this->assertEquals('44a6bec6-2323-590f-85a7-fabc6c2282c9', $event->getId());
  }

  /**
   * Test generateEventId for login event with fallback to last login time.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdLoginFallback(): void {
    // The request is already set up in setUp() with an empty session ID,
    // which triggers the fallback to last login time.
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.login');

    $this->assertEquals('52c40fe8-4ca5-53c6-a381-3df93fbf344a', $event->getId());
  }

  /**
   * Test generateEventId for logout event with session ID.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdLogout(): void {
    // Mock session with a session ID.
    $session = $this->createMock(SessionInterface::class);
    $session->method('getId')->willReturn('test-session-id-12345');

    // Create a new request with the session.
    $request = $this->createMock(Request::class);
    $request->method('getUri')->willReturn('http://example.com/user/register');
    $request->method('getPathInfo')->willReturn('/user/register');
    $request->method('getSession')->willReturn($session);

    // Create a new request stack with the request.
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($request);

    /** @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\RequestStack $requestStack */
    $handler = new EdaHandler(
      $this->dispatcher,
      $requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
    );

    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.logout');

    $this->assertEquals('7c3a9772-1562-56f8-a912-bb6059dda52d', $event->getId());
  }

  /**
   * Test generateEventId for logout event with fallback to last login time.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdLogoutFallback(): void {
    // The request is already set up in setUp() with an empty session ID,
    // which triggers the fallback to last login time.
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.logout');

    $this->assertEquals('87141117-6968-540e-b47c-903f0215e7b2', $event->getId());
  }

  /**
   * Test generateEventId for pending event (includes changed timestamp).
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdPending(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.pending');

    $this->assertEquals('4b1acff8-d814-5434-92fd-42355d9b7ea0', $event->getId());
  }

  /**
   * Test generateEventId for profile.update event (includes changed timestamp).
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdProfileUpdate(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.profile.update');

    $this->assertEquals('905d171a-029b-516e-94b9-e68b982b1f4b', $event->getId());
  }

  /**
   * Test generateEventId for block event (includes changed timestamp).
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdBlock(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.block');

    $this->assertEquals('2e63c892-7a6a-51bd-9987-4387932e2c32', $event->getId());
  }

  /**
   * Test generateEventId for unblock event (includes changed timestamp).
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdUnblock(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.unblock');

    $this->assertEquals('c79c4cd8-91b2-5d3e-9087-ad3584466151', $event->getId());
  }

  /**
   * Test generateEventId for settings.email event (includes changed timestamp).
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdSettingsEmail(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.settings.email');

    $this->assertEquals('97706595-676b-58bc-813c-deb893c3560c', $event->getId());
  }

  /**
   * Test generateEventId for settings.locale event.
   *
   * @covers \Drupal\social_user\EdaHandler::fromEntity
   * @covers \Drupal\social_user\EdaHandler::generateEventId
   */
  public function testGenerateEventIdSettingsLocale(): void {
    $handler = $this->getMockedHandler();
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.settings.locale');

    $this->assertEquals('2cfaa05a-9041-5a5e-bc6d-35ae78acf0c6', $event->getId());
  }

  /**
   * Test the userCreate() method.
   *
   * @covers ::userCreate
   */
  public function testUserCreate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.create');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userCreate method.
    $handler->userCreate($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.create', $event->getType());
  }

  /**
   * Test the userPending() method.
   *
   * @covers ::userPending
   */
  public function testUserPending(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.pending');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userPending method.
    $handler->userPending($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.pending', $event->getType());
  }

  /**
   * Test the profileUpdate() method.
   *
   * @covers ::profileUpdate
   */
  public function testProfileUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.profile.update');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the profileUpdate method.
    $handler->profileUpdate($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.profile.update', $event->getType());
  }

  /**
   * Test the userLogin() method.
   *
   * @covers ::userLogin
   */
  public function testUserLogin(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.login');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userLogin method.
    $handler->userLogin($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.login', $event->getType());
  }

  /**
   * Test the userLogout() method.
   *
   * @covers ::userLogout
   */
  public function testUserLogout(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.logout');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userLogout method.
    $handler->userLogout($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.logout', $event->getType());
  }

  /**
   * Test the userBlock() method.
   *
   * @covers ::userBlock
   */
  public function testUserBlock(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.block');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userBlock method.
    $handler->userBlock($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.block', $event->getType());
  }

  /**
   * Test the userUnblock() method.
   *
   * @covers ::userUnblock
   */
  public function testUserUnblock(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.unblock');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userUnblock method.
    $handler->userUnblock($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.unblock', $event->getType());
  }

  /**
   * Test the userDelete() method.
   *
   * @covers ::userDelete
   */
  public function testUserDelete(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.delete');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userDelete method.
    $handler->userDelete($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.delete', $event->getType());
  }

  /**
   * Test the userEmailUpdate() method.
   *
   * @covers ::userEmailUpdate
   */
  public function testUserEmailUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.settings.email');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userEmailUpdate method.
    $handler->userEmailUpdate($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.settings.email', $event->getType());
  }

  /**
   * Test the userLocaleInformationUpdate() method.
   *
   * @covers ::userLocaleInformationUpdate
   */
  public function testUserLocaleInformationUpdate(): void {
    // Create the handler instance.
    $handler = $this->getMockedHandler();

    // Create the event object.
    $event = $handler->fromEntity($this->user, 'com.getopensocial.cms.user.settings.locale');

    // Expect the dispatch method in the dispatcher to be called.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.user.v1'),
        $this->equalTo($event)
      );

    // Call the userLocaleInformationUpdate method.
    $handler->userLocaleInformationUpdate($this->user);

    // Assert that the correct event is dispatched.
    $this->assertEquals('com.getopensocial.cms.user.settings.locale', $event->getType());
  }

  /**
   * Returns a mocked handler with dependencies injected.
   *
   * @return \Drupal\social_user\EdaHandler
   *   The mocked handler instance.
   */
  protected function getMockedHandler(): EdaHandler {
    return new EdaHandler(
      $this->dispatcher,
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
