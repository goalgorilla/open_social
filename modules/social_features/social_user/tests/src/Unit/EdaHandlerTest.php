<?php

namespace Drupal\Tests\social_user\Unit;

use CloudEvents\V1\CloudEvent;
use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Uuid\UuidInterface;
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
use Drupal\social_eda_dispatcher\Dispatcher as SocialEdaDispatcher;
use Drupal\social_user\EdaHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
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
   * The dispatcher for sending CloudEvents.
   *
   * @var \Drupal\social_eda_dispatcher\Dispatcher
   */
  protected $dispatcher;

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
   * Set up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the language_manager service using Prophecy.
    $languageManagerMock = $this->prophesize(LanguageManagerInterface::class);
    $languageMock = $this->prophesize(LanguageInterface::class);
    $languageMock->getId()->willReturn('en');
    $languageManagerMock->getCurrentLanguage()->willReturn($languageMock->reveal());

    // Set up Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock->reveal());
    \Drupal::setContainer($container);

    // Mock dependencies using Prophecy.
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
    $this->dispatcher = $this->prophesize(SocialEdaDispatcher::class)->reveal();

    // Resolve UUID.
    $uuidMock = $this->prophesize(UuidInterface::class);
    $uuidMock->generate()->willReturn('a5715874-5859-4d8a-93ba-9f8433ea44af');
    $this->uuid = $uuidMock->reveal();

    // Mock the EntityTypeManagerInterface and ProfileStorageInterface.
    $profileStorage = $this->prophesize(ProfileStorageInterface::class);
    $userStorage = $this->prophesize(EntityStorageInterface::class);

    // Mock the user entity to be returned by load().
    $userMock = $this->prophesize(UserInterface::class);
    $userMock->get('uuid')->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $userStorage->load(1)->willReturn($userMock->reveal());

    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('profile')->willReturn($profileStorage->reveal());
    $entityTypeManagerMock->getStorage('user')->willReturn($userStorage->reveal());

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

    // Mock the URL object.
    $urlMock = $this->prophesize(Url::class);
    $urlMock->toString()->willReturn('http://example.com');
    $this->url = $urlMock->reveal();

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
    $handler = new EdaHandler(
      $this->dispatcher,
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch
    );

    // Mock the User entity using Prophecy.
    $user = $this->prophesize(UserInterface::class);
    $user->get('uuid')->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
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

    // Mock the Profile entity using Prophecy.
    $profile = $this->prophesize(ProfileInterface::class);
    $profile->get('field_profile_first_name')->willReturn((object) ['value' => 'First']);
    $profile->get('field_profile_last_name')->willReturn((object) ['value' => 'Last']);
    $profile->get('field_profile_phone_number')->willReturn((object) ['value' => '123456789']);
    $profile->get('field_profile_function')->willReturn((object) ['value' => 'Developer']);
    $profile->get('field_profile_organization')->willReturn((object) ['value' => 'Organization']);
    $profile->get('field_profile_address')->willReturn($this->addressItemList);
    $profileMock = $profile->reveal();

    // Mock the ProfileStorageInterface.
    $profileStorage = $this->prophesize(ProfileStorageInterface::class);
    $profileStorage->loadByUser($userMock, 'profile')->willReturn($profileMock);

    // Mock the EntityTypeManagerInterface.
    $entityTypeManagerMock = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManagerMock->getStorage('profile')->willReturn($profileStorage->reveal());
    $this->entityTypeManager = $entityTypeManagerMock->reveal();

    // Test the fromEntity method.
    $event = $handler->fromEntity($userMock, 'com.getopensocial.cms.user.create');

    // Assert the result is an instance of CloudEvent.
    $this->assertInstanceOf(CloudEvent::class, $event);
  }

}
