<?php

namespace Drupal\Tests\social_user\Unit;

use CloudEvents\V1\CloudEvent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_eda_dispatcher\Dispatcher as SocialEdaDispatcher;
use Drupal\social_user\EdaHandler;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\social_user\EdaHandler
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
   * Handles entity type manager operations.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Represents the current user.
   */
  protected AccountProxyInterface $account;

  /**
   * Represents the route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Set up the test environment.
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
  }

  /**
   * Test method fromEntity().
   *
   * @covers ::fromEntity
   */
  public function testFromEntity(): void {
    // Mock the User entity.
    $user = $this->createMock(UserInterface::class);
    $user->method('get')->with('uuid')->willReturn((object) ['value' => 'a5715874-5859-4d8a-93ba-9f8433ea44af']);
    $user->method('getCreatedTime')->willReturn(1692614400);
    $user->method('getChangedTime')->willReturn(1692618000);
    $user->method('isActive')->willReturn(TRUE);
    $user->method('getDisplayName')->willReturn('User Name');
    $user->method('getEmail')->willReturn('user@example.com');
    $user->method('getRoles')->willReturn(['authenticated']);
    $user->method('getPreferredLangcode')->willReturn('en');
    $user->method('getTimeZone')->willReturn('UTC');

    // Mock the Profile entity.
    $profile = $this->createMock(ProfileInterface::class);
    $profile->method('get')->will($this->returnValueMap([
      ['field_profile_first_name', (object) ['value' => 'First']],
      ['field_profile_last_name', (object) ['value' => 'Last']],
      ['field_profile_phone_number', (object) ['value' => '123456789']],
      ['field_profile_function', (object) ['value' => 'Developer']],
      ['field_profile_organization', (object) ['value' => 'Organization']],
      ['field_profile_address', (object) ['value' => 'Address']],
    ]));

    // Mock the entityTypeManager to return the profile.
    $profileStorage = $this->createMock(EntityTypeManagerInterface::class);
    $profileStorage->method('loadByUser')->willReturn($profile);
    $this->entityTypeManager->method('getStorage')->willReturn($profileStorage);

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

    // Test the fromEntity method.
    $event = $handler->fromEntity($user);

    // Assert the result is an instance of CloudEvent.
    $this->assertInstanceOf(CloudEvent::class, $event);

    // Assert the event data.
    $this->assertEquals('com.getopensocial.cms.user.create', $event->getType());
    $this->assertEquals('/user/register', $event->getSource());
    $this->assertEquals('a5715874-5859-4d8a-93ba-9f8433ea44af', $event->getId());
    $this->assertEquals('User Name', $event->getData()['user']->displayName);
  }
}
