<?php

namespace Drupal\Tests\social_user\Unit;

use Consolidation\Config\ConfigInterface;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\social_eda\DispatcherInterface;
use Drupal\social_user\EdaHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
   */
  protected MockObject $dispatcher;

  /**
   * The UUID generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The HTTP request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Set up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the language_manager service using Prophecy.
    $languageManagerMock = $this->prophesize(LanguageManagerInterface::class);
    $languageMock = $this->prophesize(LanguageInterface::class);
    $languageMock->getId()->willReturn('en');
    $languageManagerMock->getCurrentLanguage()->willReturn($languageMock->reveal());

    // Mock the configuration for `social_eda.settings.namespaces`.
    $configMock = $this->prophesize(ConfigInterface::class);
    $configMock->get('namespace')->willReturn('com.getopensocial');

    $configFactoryMock = $this->prophesize(ConfigFactoryInterface::class);
    $configFactoryMock->get('social_eda.settings')->willReturn($configMock->reveal());
    $this->configFactory = $configFactoryMock->reveal();

    // Set up Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock->reveal());
    \Drupal::setContainer($container);

    // Prophesize the module handler and ensure `social_eda` is enabled.
    $moduleHandlerProphecy = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandlerProphecy->moduleExists('social_eda')->willReturn(TRUE);
    $moduleHandlerProphecy->moduleExists('social_eda_dispatcher')->willReturn(TRUE);
    $this->moduleHandler = $moduleHandlerProphecy->reveal();

    // Prophesize the Dispatcher service.
    $this->dispatcher = $this->getMockBuilder(DispatcherInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Resolve UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Mock the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

    // Mock the EntityTypeManagerInterface and ProfileStorageInterface.
    $profileStorage = $this->prophesize(ProfileStorageInterface::class);

    // Mock the User entity using Prophecy.
    $user = $this->prophesize(UserInterface::class);
    $user->get('uuid')->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $user->uuid()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $user->id()->willReturn(1);
    $user->getCreatedTime()->willReturn(1692614400);
    $user->getChangedTime()->willReturn(1692618000);
    $user->isActive()->willReturn(TRUE);
    $user->getDisplayName()->willReturn('User Name');
    $user->getEmail()->willReturn('user@example.com');
    $user->getRoles()->willReturn(['authenticated']);
    $user->getPreferredLangcode()->willReturn('en');
    $user->getTimeZone()->willReturn('UTC');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->url);
    $userMock = $user->reveal();
    $this->user = $userMock;

    $userStorage = $this->prophesize(EntityStorageInterface::class);
    $userStorage->load(1)->willReturn($userMock);

    // Mock the Profile entity using Prophecy.
    $profile = $this->prophesize(ProfileInterface::class);
    $profile->get('field_profile_first_name')->willReturn((object) ['value' => 'First']);
    $profile->get('field_profile_last_name')->willReturn((object) ['value' => 'Last']);
    $profile->get('field_profile_phone_number')->willReturn((object) ['value' => '123456789']);
    $profile->get('field_profile_function')->willReturn((object) ['value' => 'Developer']);
    $profile->get('field_profile_organization')->willReturn((object) ['value' => 'Organization']);
    $profile->get('field_profile_address')->willReturn($this->addressItemList);
    $profileMock = $profile->reveal();
    $this->profile = $profileMock;

    // Mock the EntityTypeManagerInterface.
    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('profile')->willReturn($profileStorage->reveal());
    $entityTypeManagerMock->getStorage('user')->willReturn($userStorage->reveal());
    $this->entityTypeManager = $entityTypeManagerMock->reveal();

    // Mock the AccountProxyInterface.
    $accountMock = $this->prophesize(AccountProxyInterface::class);
    $accountMock->id()->willReturn(1);
    $this->account = $accountMock->reveal();

    // Mock the RouteMatchInterface.
    $routeMatchMock = $this->prophesize(RouteMatchInterface::class);
    $routeMatchMock->getRouteName()->willReturn('entity.node.edit_form');
    $this->routeMatch = $routeMatchMock->reveal();

    // Mock the Request and RequestStack using Prophecy.
    $requestMock = $this->prophesize(Request::class);
    $requestMock->getUri()->willReturn('http://example.com/user/register');
    $requestMock->getPathInfo()->willReturn('/user/register');
    $this->request = $requestMock->reveal();

    $requestStackMock = $this->prophesize(RequestStack::class);
    $requestStackMock->getCurrentRequest()->willReturn($this->request);
    $this->requestStack = $requestStackMock->reveal();

    // Mock the Address field.
    $addressItemMock = $this->prophesize(AddressItem::class);
    $this->addressItem = $addressItemMock->reveal();

    $addressItemListMock = $this->prophesize(AddressFieldItemList::class);
    $addressItemListMock->first()->willReturn($this->addressItem);
    $this->addressItemList = $addressItemListMock->reveal();

    // Finally, reveal the entity type manager.
    $this->entityTypeManager = $entityTypeManagerMock->reveal();
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
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
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
        $this->equalTo('com.getopensocial.cms.user.settings.locale'),
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
      // @phpstan-ignore-next-line
      $this->dispatcher,
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
    );
  }

}
