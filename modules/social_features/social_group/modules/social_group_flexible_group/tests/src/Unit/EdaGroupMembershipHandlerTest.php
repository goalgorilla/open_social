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
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
   * @var \Drupal\Component\Uuid\UuidInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $uuid;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $account;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $time;

  /**
   * The EDA dispatcher.
   *
   * @var \Drupal\social_eda\DispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $dispatcher;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

  /**
   * {@inheritdoc}
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

    $this->uuid = $this->prophesize(UuidInterface::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $userStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage('user')->willReturn($userStorage->reveal());
    $this->account = $this->prophesize(AccountProxyInterface::class);
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->time = $this->prophesize(TimeInterface::class);
    $this->dispatcher = $this->prophesize(DispatcherInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);

    // Set up basic mocks.
    $this->uuid->generate()->willReturn('test-uuid-123');
    $this->time->getRequestTime()->willReturn(1234567890);
    $this->account->id()->willReturn(1);

    // Set up request stack.
    $request = $this->prophesize(Request::class);
    $request->getPathInfo()->willReturn('/group/1/join');
    $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

    // Set up route match.
    $this->routeMatch->getRouteName()->willReturn('entity.group.join');

    // Set up config factory.
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('namespace')->willReturn('com.getopensocial');
    $this->configFactory->get('social_eda.settings')->willReturn($config->reveal());

    // Set up module handler.
    $this->moduleHandler->moduleExists('social_eda')->willReturn(TRUE);

    // Set up logger factory.
    $this->loggerFactory->get('social_group_flexible_group')->willReturn($this->logger->reveal());

    $this->edaHandler = new EdaGroupMembershipHandler(
      $this->uuid->reveal(),
      $this->requestStack->reveal(),
      $this->moduleHandler->reveal(),
      $this->entityTypeManager->reveal(),
      $this->account->reveal(),
      $this->routeMatch->reveal(),
      $this->configFactory->reveal(),
      $this->time->reveal(),
      $this->loggerFactory->reveal(),
      $this->dispatcher->reveal()
    );
  }

  /**
   * Tests group membership creation.
   */
  public function testGroupMembershipCreate(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->getGroup()->willReturn($group->reveal());
    $membership->getEntity()->willReturn($user->reveal());
    $membership->uuid()->willReturn('membership-uuid');
    $membership->getCreatedTime()->willReturn(1234567890);
    $membership->getChangedTime()->willReturn(1234567890);
    $membership->hasField('group_roles')->willReturn(TRUE);
    $membership->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->id()->willReturn(123);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipCreate($membership->reveal());
  }

  /**
   * Tests group membership deletion.
   */
  public function testGroupMembershipDelete(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->getGroup()->willReturn($group->reveal());
    $membership->getEntity()->willReturn($user->reveal());
    $membership->uuid()->willReturn('membership-uuid');
    $membership->getCreatedTime()->willReturn(1234567890);
    $membership->getChangedTime()->willReturn(1234567890);
    $membership->hasField('group_roles')->willReturn(TRUE);
    $membership->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->id()->willReturn(123);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipDelete($membership->reveal());
  }

  /**
   * Tests group membership request creation.
   */
  public function testGroupMembershipRequestCreate(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->getGroup()->willReturn($group->reveal());
    $request->getEntity()->willReturn($user->reveal());
    $request->uuid()->willReturn('request-uuid');
    $request->getCreatedTime()->willReturn(1234567890);
    $request->getChangedTime()->willReturn(1234567890);
    $request->label()->willReturn('Request to join group');
    $request->hasField('group_roles')->willReturn(TRUE);
    $request->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->id()->willReturn(456);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipRequestCreate($request->reveal());
  }

  /**
   * Tests group membership request deletion.
   */
  public function testGroupMembershipRequestDelete(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->getGroup()->willReturn($group->reveal());
    $request->getEntity()->willReturn($user->reveal());
    $request->uuid()->willReturn('request-uuid');
    $request->getCreatedTime()->willReturn(1234567890);
    $request->getChangedTime()->willReturn(1234567890);
    $request->label()->willReturn('Request to join group');
    $request->hasField('group_roles')->willReturn(TRUE);
    $request->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->id()->willReturn(456);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipRequestDelete($request->reveal());
  }

  /**
   * Tests group membership request acceptance.
   */
  public function testGroupMembershipRequestAccept(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->getGroup()->willReturn($group->reveal());
    $request->getEntity()->willReturn($user->reveal());
    $request->uuid()->willReturn('request-uuid');
    $request->getCreatedTime()->willReturn(1234567890);
    $request->getChangedTime()->willReturn(1234567890);
    $request->label()->willReturn('Request to join group');
    $request->hasField('group_roles')->willReturn(TRUE);
    $request->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->id()->willReturn(456);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipRequestAccept($request->reveal());
  }

  /**
   * Tests group membership request decline.
   */
  public function testGroupMembershipRequestDecline(): void {
    $request = $this->createRequestMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $request->getGroup()->willReturn($group->reveal());
    $request->getEntity()->willReturn($user->reveal());
    $request->uuid()->willReturn('request-uuid');
    $request->getCreatedTime()->willReturn(1234567890);
    $request->getChangedTime()->willReturn(1234567890);
    $request->label()->willReturn('Request to join group');
    $request->hasField('group_roles')->willReturn(TRUE);
    $request->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $request->id()->willReturn(456);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipRequestDecline($request->reveal());
  }

  /**
   * Tests group membership invite creation.
   */
  public function testGroupMembershipInviteCreate(): void {
    $invitation = $this->createInvitationMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $invitation->getGroup()->willReturn($group->reveal());
    $invitation->getEntity()->willReturn($user->reveal());
    $invitation->uuid()->willReturn('invitation-uuid');
    $invitation->getCreatedTime()->willReturn(1234567890);
    $invitation->getChangedTime()->willReturn(1234567890);
    $invitation->label()->willReturn('Invitation to join group');
    $invitation->hasField('group_roles')->willReturn(TRUE);
    $invitation->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $invitation->id()->willReturn(789);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->shouldBeCalledOnce();

    $this->edaHandler->groupMembershipInviteCreate($invitation->reveal());
  }

  /**
   * Tests that no dispatch occurs when module is not enabled.
   */
  public function testNoDispatchWhenModuleNotEnabled(): void {
    $this->moduleHandler->moduleExists('social_eda')->willReturn(FALSE);

    $edaHandler = new EdaGroupMembershipHandler(
      $this->uuid->reveal(),
      $this->requestStack->reveal(),
      $this->moduleHandler->reveal(),
      $this->entityTypeManager->reveal(),
      $this->account->reveal(),
      $this->routeMatch->reveal(),
      $this->configFactory->reveal(),
      $this->time->reveal(),
      $this->loggerFactory->reveal(),
      $this->dispatcher->reveal()
    );

    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->getGroup()->willReturn($group->reveal());
    $membership->getEntity()->willReturn($user->reveal());

    $this->dispatcher->dispatch(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    $edaHandler->groupMembershipCreate($membership->reveal());
  }

  /**
   * Tests that no dispatch occurs when dispatcher is not available.
   */
  public function testNoDispatchWhenDispatcherNotAvailable(): void {
    $this->moduleHandler->moduleExists('social_eda')->willReturn(TRUE);

    $edaHandler = new EdaGroupMembershipHandler(
      $this->uuid->reveal(),
      $this->requestStack->reveal(),
      $this->moduleHandler->reveal(),
      $this->entityTypeManager->reveal(),
      $this->account->reveal(),
      $this->routeMatch->reveal(),
      $this->configFactory->reveal(),
      $this->time->reveal(),
      $this->loggerFactory->reveal(),
      NULL
    );

    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->getGroup()->willReturn($group->reveal());
    $membership->getEntity()->willReturn($user->reveal());

    // Should not throw an error when dispatcher is NULL.
    $edaHandler->groupMembershipCreate($membership->reveal());
  }

  /**
   * Tests error logging when dispatch fails.
   */
  public function testErrorLoggingOnDispatchFailure(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();

    $membership->getGroup()->willReturn($group->reveal());
    $membership->getEntity()->willReturn($user->reveal());
    $membership->uuid()->willReturn('membership-uuid');
    $membership->getCreatedTime()->willReturn(1234567890);
    $membership->getChangedTime()->willReturn(1234567890);
    $membership->hasField('group_roles')->willReturn(TRUE);
    $membership->get('group_roles')->willReturn($this->createFieldItemListMock([]));
    $membership->id()->willReturn(123);

    $group->uuid()->willReturn('group-uuid');
    $group->label()->willReturn('Test Group');
    $group->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/group/1'));

    $user->uuid()->willReturn('user-uuid');
    $user->getDisplayName()->willReturn('Test User');
    $user->toUrl('canonical', ['absolute' => TRUE])->willReturn($this->createUrlMock('https://example.com/user/1'));
    $user->isAnonymous()->willReturn(FALSE);

    // Make the dispatcher throw an exception.
    $this->dispatcher->dispatch('com.getopensocial.cms.group_membership.v1', Argument::any())
      ->willThrow(new \Exception('Test dispatch error'));

    // Expect error logging with the actual error that occurs.
    $this->logger->error('Failed to dispatch EDA event for group membership. Topic: @topic, Event type: @event_type, Group Membership ID: @membership_id, Error: @error', Argument::type('array'))
      ->shouldBeCalledOnce();

    // This should not throw an exception due to the try-catch block.
    $this->edaHandler->groupMembershipCreate($membership->reveal());
  }

  /**
   * Creates a mock GroupMembershipInterface.
   */
  protected function createMembershipMock(): ObjectProphecy {
    return $this->prophesize(GroupMembershipInterface::class);
  }

  /**
   * Creates a mock GroupRelationshipInterface for requests.
   */
  protected function createRequestMock(): ObjectProphecy {
    return $this->prophesize(GroupRelationshipInterface::class);
  }

  /**
   * Creates a mock GroupRelationshipInterface for invitations.
   */
  protected function createInvitationMock(): ObjectProphecy {
    return $this->prophesize(GroupRelationshipInterface::class);
  }

  /**
   * Creates a mock GroupInterface.
   */
  protected function createGroupMock(): ObjectProphecy {
    return $this->prophesize(GroupInterface::class);
  }

  /**
   * Creates a mock UserInterface.
   */
  protected function createUserMock(): ObjectProphecy {
    return $this->prophesize(UserInterface::class);
  }

  /**
   * Creates a mock field item list.
   */
  protected function createFieldItemListMock(array $values): ObjectProphecy {
    $fieldItemList = $this->prophesize(FieldItemListInterface::class);
    $fieldItemList->getValue()->willReturn($values);
    return $fieldItemList;
  }

  /**
   * Creates a mock URL object.
   */
  protected function createUrlMock(string $url): ObjectProphecy {
    $urlObject = $this->prophesize(Url::class);
    $urlObject->toString()->willReturn($url);
    return $urlObject;
  }

}
