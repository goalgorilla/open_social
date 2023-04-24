<?php

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Drupal\social_group\GroupMuteNotify;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for the flag action plugin.
 *
 * @group social_group
 *
 * @coversDefaultClass \Drupal\social_group\GroupMuteNotify
 */
class GroupNotifyTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * A dummy group to use in other prophecies.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected FlagService $flagService;

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected GroupMuteNotify $groupNotifyService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected EntityTypeRepositoryInterface|ProphecyInterface $entityTypeRepository;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected ContainerInterface|ProphecyInterface $container;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $prophet = new Prophet();
    $this->group = $prophet->prophesize(GroupInterface::class)->reveal();
    $this->flagService = $prophet->prophesize(FlagService::class)->reveal();

    $account = $prophet->prophesize(AccountProxyInterface::class)->reveal();

    // Mock the mute_group_notifications flag.
    $entityFlagMock = $this->getMockBuilder(FlagInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityFlagMock->expects($this->any())
      ->method('id')
      ->will($this->returnValue('mute_group_notifications'));

    // Mock the Entity Storage & Entity Type Manager for groupNOtifyIsMuted.
    $entityStorage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityStorage->expects($this->any())
      ->method('load')
      ->willReturn($entityFlagMock);

    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getstorage')
      ->willReturn($entityStorage);

    $this->entityTypeManager = $entityTypeManager;

    // Make sure the entity type manager has the necessary mocked input for
    // groupNotifyIsMuted to run and especially the user being the AN mocked
    // user.
    $container = new ContainerBuilder();
    $container->set('current_user', $account);
    $container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($container);
    $this->currentUser = \Drupal::currentUser();
  }

  /**
   * Tests GroupNotify.
   *
   * @covers ::groupNotifyIsMuted
   */
  public function testGroupNotifyAsAnonymous(): void {
    $this->groupNotifyService = new GroupMuteNotify(
      $this->flagService,
      $this->entityTypeManager,
    );

    try {
      // Ensure for AN users it doesn't result in an exception
      // rather it returns FALSE as no flags were found.
      $flags = $this->groupNotifyService->groupNotifyIsMuted($this->group, $this->currentUser);
      $this->assertEquals(FALSE, $flags);
    }
    catch (\LogicException $e) {
      $this->fail('An exception was thrown while it should not');
    }
  }

}
