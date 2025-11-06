<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\social_group_flexible_group\EdaGroupMembershipHandler;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\social_eda\DispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the EdaGroupMembershipHandler class.
 *
 * @group social_group_flexible_group
 */
class EdaGroupMembershipHandlerTest extends UnitTestCase {

  /**
   * The EDA handler under test.
   *
   * @var \Drupal\social_group_flexible_group\EdaGroupMembershipHandler
   */
  protected EdaGroupMembershipHandler $edaHandler;

  /**
   * The UUID service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The request stack.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The route match.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory.
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
   * The EDA dispatcher.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\social_eda\DispatcherInterface
   */
  protected $dispatcher;

  /**
   * The logger factory.
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the language_manager service.
    $languageMock = $this->createMock(LanguageInterface::class);
    $languageMock->method('getId')->willReturn('en');
    $languageManagerMock = $this->createMock(LanguageManagerInterface::class);
    $languageManagerMock->method('getCurrentLanguage')->willReturn($languageMock);

    // Mock Drupal's container.
    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManagerMock);
    \Drupal::setContainer($container);

    $this->uuid = $this->createMock(UuidInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')->with('user')->willReturn($userStorage);
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->dispatcher = $this->createMock(DispatcherInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Set up basic mocks.
    $this->uuid->method('generate')->willReturn('test-uuid-123');
    $this->time->method('getRequestTime')->willReturn(1234567890);
    $this->account->method('id')->willReturn(1);

    // Set up request stack.
    $request = $this->createMock(Request::class);
    $request->method('getPathInfo')->willReturn('/group/1/join');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    // Set up route match.
    $this->routeMatch->method('getRouteName')->willReturn('entity.group.join');

    // Set up config factory.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('namespace')->willReturn('com.getopensocial');
    $this->configFactory->method('get')->with('social_eda.settings')->willReturn($config);

    // Set up module handler.
    $this->moduleHandler->method('moduleExists')->with('social_eda')->willReturn(TRUE);

    // Set up logger factory.
    $this->loggerFactory->method('get')->with('social_group_flexible_group')->willReturn($this->logger);

    $this->edaHandler = new EdaGroupMembershipHandler(
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher
    );
  }

  /**
   * Tests group membership creation.
   */
  public function testGroupMembershipCreate(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->method('getGroup')->willReturn($group);
    $membership->method('getEntity')->willReturn($user);
    $membership->method('uuid')->willReturn('membership-uuid');
    $membership->method('getCreatedTime')->willReturn(1234567890);
    $membership->method('getChangedTime')->willReturn(1234567890);
    $membership->method('hasField')->with('group_roles')->willReturn(TRUE);
    $membership->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->method('id')->willReturn(123);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipCreate($membership);
  }

  /**
   * Tests group membership deletion.
   */
  public function testGroupMembershipDelete(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->method('getGroup')->willReturn($group);
    $membership->method('getEntity')->willReturn($user);
    $membership->method('uuid')->willReturn('membership-uuid');
    $membership->method('getCreatedTime')->willReturn(1234567890);
    $membership->method('getChangedTime')->willReturn(1234567890);
    $membership->method('hasField')->with('group_roles')->willReturn(TRUE);
    $membership->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->method('id')->willReturn(123);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipDelete($membership);
  }

  /**
   * Tests group membership request creation.
   */
  public function testGroupMembershipRequestCreate(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->method('getGroup')->willReturn($group);
    $request->method('getEntity')->willReturn($user);
    $request->method('uuid')->willReturn('request-uuid');
    $request->method('getCreatedTime')->willReturn(1234567890);
    $request->method('getChangedTime')->willReturn(1234567890);
    $request->method('label')->willReturn('Request to join group');
    $request->method('hasField')->with('group_roles')->willReturn(TRUE);
    $request->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->method('id')->willReturn(456);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipRequestCreate($request);
  }

  /**
   * Tests group membership request deletion.
   */
  public function testGroupMembershipRequestDelete(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->method('getGroup')->willReturn($group);
    $request->method('getEntity')->willReturn($user);
    $request->method('uuid')->willReturn('request-uuid');
    $request->method('getCreatedTime')->willReturn(1234567890);
    $request->method('getChangedTime')->willReturn(1234567890);
    $request->method('label')->willReturn('Request to join group');
    $request->method('hasField')->with('group_roles')->willReturn(TRUE);
    $request->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->method('id')->willReturn(456);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipRequestDelete($request);
  }

  /**
   * Tests group membership request acceptance.
   */
  public function testGroupMembershipRequestAccept(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->method('getGroup')->willReturn($group);
    $request->method('getEntity')->willReturn($user);
    $request->method('uuid')->willReturn('request-uuid');
    $request->method('getCreatedTime')->willReturn(1234567890);
    $request->method('getChangedTime')->willReturn(1234567890);
    $request->method('label')->willReturn('Request to join group');
    $request->method('hasField')->with('group_roles')->willReturn(TRUE);
    $request->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->method('id')->willReturn(456);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipRequestAccept($request);
  }

  /**
   * Tests group membership request decline.
   */
  public function testGroupMembershipRequestDecline(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->method('getGroup')->willReturn($group);
    $request->method('getEntity')->willReturn($user);
    $request->method('uuid')->willReturn('request-uuid');
    $request->method('getCreatedTime')->willReturn(1234567890);
    $request->method('getChangedTime')->willReturn(1234567890);
    $request->method('label')->willReturn('Request to join group');
    $request->method('hasField')->with('group_roles')->willReturn(TRUE);
    $request->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->method('id')->willReturn(456);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipRequestDecline($request);
  }

  /**
   * Tests group membership invite creation.
   */
  public function testGroupMembershipInviteCreate(): void {
    $invitation = $this->createInvitationMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $invitation->method('getGroup')->willReturn($group);
    $invitation->method('getEntity')->willReturn($user);
    $invitation->method('uuid')->willReturn('invitation-uuid');
    $invitation->method('getCreatedTime')->willReturn(1234567890);
    $invitation->method('getChangedTime')->willReturn(1234567890);
    $invitation->method('label')->willReturn('Invitation to join group');
    $invitation->method('hasField')->with('group_roles')->willReturn(TRUE);
    $invitation->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $invitation->method('id')->willReturn(789);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipInviteCreate($invitation);
  }

  /**
   * Tests group membership invite deletion.
   */
  public function testGroupMembershipInviteDelete(): void {
    $invitation = $this->createInvitationMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $invitation->method('getGroup')->willReturn($group);
    $invitation->method('getEntity')->willReturn($user);
    $invitation->method('uuid')->willReturn('invitation-uuid');
    $invitation->method('getCreatedTime')->willReturn(1234567890);
    $invitation->method('getChangedTime')->willReturn(1234567890);
    $invitation->method('label')->willReturn('Invitation to join group');
    $invitation->method('hasField')->with('group_roles')->willReturn(TRUE);
    $invitation->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $invitation->method('id')->willReturn(789);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipInviteDelete($invitation);
  }

  /**
   * Tests group membership invite acceptance.
   */
  public function testGroupMembershipInviteAccept(): void {
    $invitation = $this->createInvitationMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $invitation->method('getGroup')->willReturn($group);
    $invitation->method('getEntity')->willReturn($user);
    $invitation->method('uuid')->willReturn('invitation-uuid');
    $invitation->method('getCreatedTime')->willReturn(1234567890);
    $invitation->method('getChangedTime')->willReturn(1234567890);
    $invitation->method('label')->willReturn('Invitation to join group');
    $invitation->method('hasField')->with('group_roles')->willReturn(TRUE);
    $invitation->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $invitation->method('id')->willReturn(789);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipInviteAccept($invitation);
  }

  /**
   * Tests group membership invite decline.
   */
  public function testGroupMembershipInviteDecline(): void {
    $invitation = $this->createInvitationMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $invitation->method('getGroup')->willReturn($group);
    $invitation->method('getEntity')->willReturn($user);
    $invitation->method('uuid')->willReturn('invitation-uuid');
    $invitation->method('getCreatedTime')->willReturn(1234567890);
    $invitation->method('getChangedTime')->willReturn(1234567890);
    $invitation->method('label')->willReturn('Invitation to join group');
    $invitation->method('hasField')->with('group_roles')->willReturn(TRUE);
    $invitation->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $invitation->method('id')->willReturn(789);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      );

    $this->edaHandler->groupMembershipInviteDecline($invitation);
  }

  /**
   * Tests that no dispatch occurs when module is not enabled.
   */
  public function testNoDispatchWhenModuleNotEnabled(): void {
    $this->moduleHandler->method('moduleExists')->with('social_eda')->willReturn(FALSE);

    $edaHandler = new EdaGroupMembershipHandler(
      $this->uuid,
      $this->requestStack,
      $this->moduleHandler,
      $this->entityTypeManager,
      $this->account,
      $this->routeMatch,
      $this->configFactory,
      $this->time,
      $this->loggerFactory,
      $this->dispatcher
    );

    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->method('getGroup')->willReturn($group);
    $membership->method('getEntity')->willReturn($user);

    $this->dispatcher->expects($this->never())
      ->method('dispatch')
      ->with($this->isType('string'), $this->anything());

    $edaHandler->groupMembershipCreate($membership);
  }

  /**
   * Tests that no dispatch occurs when dispatcher is not available.
   */
  public function testNoDispatchWhenDispatcherNotAvailable(): void {
    $this->moduleHandler->method('moduleExists')->with('social_eda')->willReturn(TRUE);

    $edaHandler = new EdaGroupMembershipHandler(
      $this->uuid,
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

    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->method('getGroup')->willReturn($group);
    $membership->method('getEntity')->willReturn($user);

    // Should not throw an error when dispatcher is NULL.
    $edaHandler->groupMembershipCreate($membership);
  }

  /**
   * Tests error logging when dispatch fails.
   */
  public function testErrorLoggingOnDispatchFailure(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->method('getGroup')->willReturn($group);
    $membership->method('getEntity')->willReturn($user);
    $membership->method('uuid')->willReturn('membership-uuid');
    $membership->method('getCreatedTime')->willReturn(1234567890);
    $membership->method('getChangedTime')->willReturn(1234567890);
    $membership->method('hasField')->with('group_roles')->willReturn(TRUE);
    $membership->method('get')->with('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->method('id')->willReturn(123);

    $group->method('uuid')->willReturn('group-uuid');
    $group->method('label')->willReturn('Test Group');
    $group->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->method('uuid')->willReturn('user-uuid');
    $user->method('getDisplayName')->willReturn('Test User');
    $user->method('toUrl')
      ->with('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])
      ->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->method('isAnonymous')->willReturn(FALSE);

    // Make the dispatcher throw an exception.
    $this->dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->equalTo('com.getopensocial.cms.group_membership.v1'),
        $this->anything()
      )
      ->willThrowException(new \Exception('Test dispatch error'));

    // Expect error logging with the actual error that occurs.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->equalTo('Failed to dispatch EDA event for group membership. Topic: @topic, Event type: @event_type, Group Membership ID: @membership_id, Error: @error'),
        $this->isType('array')
      );

    // This should not throw an exception due to the try-catch block.
    $this->edaHandler->groupMembershipCreate($membership);
  }

  /**
   * Creates a mock GroupMembershipInterface.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\group\Entity\GroupMembershipInterface
   *   A mocked GroupMembershipInterface instance.
   */
  protected function createMembershipMock() {
    return $this->createMock(GroupMembershipInterface::class);
  }

  /**
   * Creates a mock GroupRelationshipInterface for requests.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\group\Entity\GroupRelationshipInterface
   *   A mocked GroupRelationshipInterface instance for requests.
   */
  protected function createRequestMock() {
    return $this->createMock(GroupRelationshipInterface::class);
  }

  /**
   * Creates a mock GroupRelationshipInterface for invitations.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\group\Entity\GroupRelationshipInterface
   *   A mocked GroupRelationshipInterface instance for invitations.
   */
  protected function createInvitationMock() {
    return $this->createMock(GroupRelationshipInterface::class);
  }

  /**
   * Creates a mock GroupInterface.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\group\Entity\GroupInterface
   *   A mocked GroupInterface instance.
   */
  protected function createGroupMock() {
    return $this->createMock(GroupInterface::class);
  }

  /**
   * Creates a mock UserInterface.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\user\UserInterface
   *   A mocked UserInterface instance.
   */
  protected function createUserMock() {
    return $this->createMock(UserInterface::class);
  }

  /**
   * Creates a mock field item list.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface>
   *   A mocked FieldItemListInterface instance.
   */
  protected function createFieldItemListMock(array $values) {
    $fieldItemList = $this->createMock(FieldItemListInterface::class);
    $fieldItemList->method('getValue')->willReturn($values);
    return $fieldItemList;
  }

  /**
   * Creates a mock URL object.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Url
   *   A mocked Url instance.
   */
  protected function createUrlMock(string $url) {
    $urlObject = $this->createMock(Url::class);
    $urlObject->method('toString')->willReturn($url);
    return $urlObject;
  }

}
