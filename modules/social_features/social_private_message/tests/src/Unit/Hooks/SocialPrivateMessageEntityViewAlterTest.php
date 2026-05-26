<?php

namespace Drupal\Tests\social_private_message\Unit\Hooks;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\social_private_message\Hooks\SocialPrivateMessageEntityViewAlter;
use Drupal\social_private_message\Service\SocialPrivateMessageService;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\social_private_message\Hooks\SocialPrivateMessageEntityViewAlter
 *
 * @group social_private_message
 */
class SocialPrivateMessageEntityViewAlterTest extends UnitTestCase {

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked profile entity storage (exposes loadByUser()).
   */
  protected ProfileStorageInterface&MockObject $profileStorage;

  /**
   * Mocked profile entity view builder.
   */
  protected EntityViewBuilderInterface&MockObject $profileViewBuilder;

  /**
   * Mocked current user.
   */
  protected AccountProxyInterface&MockObject $currentUser;

  /**
   * Mocked Open Social private message service.
   */
  protected SocialPrivateMessageService&MockObject $socialPrivateMessageService;

  /**
   * System under test.
   */
  protected SocialPrivateMessageEntityViewAlter $hook;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->profileStorage = $this->createMock(ProfileStorageInterface::class);
    $this->profileViewBuilder = $this->createMock(EntityViewBuilderInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->socialPrivateMessageService = $this->createMock(SocialPrivateMessageService::class);

    $this->entityTypeManager->method('getStorage')->willReturnMap([
      ['profile', $this->profileStorage],
      // For the rare User::load(1) fallback path; tests don't exercise it.
      ['user', $this->createMock(EntityStorageInterface::class)],
    ]);
    $this->entityTypeManager->method('getViewBuilder')->willReturnMap([
      ['profile', $this->profileViewBuilder],
    ]);

    $this->hook = new SocialPrivateMessageEntityViewAlter(
      $this->entityTypeManager,
      $this->currentUser,
      $this->socialPrivateMessageService,
    );

    // Wire a container so t() inside the hook resolves.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The static factory wires the constructor from the service container.
   *
   * @covers ::create
   */
  public function testCreateInstantiatesViaContainer(): void {
    $service = $this->createMock(SocialPrivateMessageService::class);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturnCallback(
      fn (string $id) => match ($id) {
        'entity_type.manager' => $this->entityTypeManager,
        'current_user' => $this->currentUser,
        'social_private_message.service' => $service,
        default => throw new \LogicException("Unexpected service id: $id"),
      }
    );

    $this->expectNotToPerformAssertions();
    SocialPrivateMessageEntityViewAlter::create($container);
  }

  /**
   * Build a UserInterface mock that returns the given uid from id().
   */
  protected function mockUser(int $uid): UserInterface {
    $user = $this->createMock(UserInterface::class);
    $user->method('id')->willReturn((string) $uid);
    return $user;
  }

  /**
   * Build a PrivateMessageThreadInterface mock for the inbox path.
   *
   * @param array $members
   *   Ordered list of UserInterface members on the thread.
   */
  protected function mockThread(array $members): PrivateMessageThreadInterface {
    $thread = $this->createMock(PrivateMessageThreadInterface::class);
    $thread->method('getEntityTypeId')->willReturn('private_message_thread');
    $thread->method('getMembers')->willReturn($members);

    $members_field = $this->createMock(FieldItemListInterface::class);
    // The hook only uses array_keys() on this — values do not matter.
    $members_field->method('getValue')->willReturn(array_map(
      fn(UserInterface $u) => ['target_id' => $u->id()],
      $members,
    ));
    $thread->method('get')->with('members')->willReturn($members_field);

    return $thread;
  }

  /**
   * Build a PrivateMessageThread (concrete class) mock for the full path.
   *
   * The 'full' view branch hands the thread to
   * SocialPrivateMessageService::updateLastThreadCheckTime(), which type-hints
   * EntityBase. The interface mock used by mockThread() does not satisfy that
   * type, so the full-view tests need a mock of the concrete class.
   */
  protected function mockThreadEntity(array $members): PrivateMessageThread {
    $thread = $this->createMock(PrivateMessageThread::class);
    $thread->method('getEntityTypeId')->willReturn('private_message_thread');
    $thread->method('getMembers')->willReturn($members);
    return $thread;
  }

  /**
   * Multi-member inbox: a member without a profile must not fatal.
   *
   * It should fall through to a 'Deleted user' fragment in
   * $build['membernames']. Reproduces PROD-35546 (S1, multi-member branch).
   *
   * @covers ::entityViewAlter
   */
  public function testInboxMultiMemberWithNullProfileFallsBackToDeletedUser(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);
    $charlie = $this->mockUser(103);

    // Current user is alice (will be unset from the loop).
    $this->currentUser->method('id')->willReturn('101');

    // Bob has a profile, charlie does NOT (the S1 condition).
    $bob_profile = $this->createMock(ProfileInterface::class);
    $this->profileStorage->method('loadByUser')->willReturnMap([
      [$bob, 'profile', $bob_profile],
      [$charlie, 'profile', NULL],
    ]);
    // ViewBuilder must be called for bob only — NOT for charlie (NULL profile).
    $this->profileViewBuilder->expects($this->once())
      ->method('view')
      ->with($bob_profile, 'name_raw')
      ->willReturn(['#markup' => 'Bob']);

    $thread = $this->mockThread([$alice, $bob, $charlie]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertArrayHasKey('membernames', $build, 'multi-member branch must populate membernames');
    $names = $build['membernames'];
    // Strip non-array entries (prefix/suffix) — keep only the rendered items.
    $rendered = array_values(array_filter($names, 'is_array'));
    $this->assertCount(2, $rendered, 'two members remain after the current user is filtered out');

    // One entry is the rendered profile (bob), the other is the deleted-user
    // markup (charlie). Order matches input order.
    $this->assertSame(['#markup' => 'Bob'], $rendered[0]);
    $this->assertArrayHasKey('#markup', $rendered[1]);
    $this->assertSame('Deleted user', (string) $rendered[1]['#markup']);
  }

  /**
   * One-on-one inbox: the recipient has no profile, hook renders the fallback.
   *
   * Reproduces PROD-35546 (S1, one-on-one branch — the exact LDES path).
   *
   * @covers ::entityViewAlter
   */
  public function testInboxOneOnOneWithNullProfileFallsBackToDeletedUser(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);

    $this->currentUser->method('id')->willReturn('101');

    // Bob has no profile.
    $this->profileStorage->method('loadByUser')->willReturn(NULL);
    // ViewBuilder MUST NOT be called with NULL — that's what fatals.
    $this->profileViewBuilder->expects($this->never())->method('view');

    $thread = $this->mockThread([$alice, $bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    // Pre-fix, this line WSOD'd. Post-fix, it must populate the fallback.
    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertArrayHasKey('membernames', $build, 'one-on-one branch must populate membernames even when the recipient profile is missing');
    $this->assertArrayHasKey('#markup', $build['membernames']);
    $this->assertSame('Deleted user', (string) $build['membernames']['#markup']);
    $this->assertSame('<strong>', $build['membernames']['#prefix']);
    $this->assertSame('</strong>', $build['membernames']['#suffix']);
    $this->assertArrayNotHasKey('members', $build, 'no profile picture is rendered when the recipient profile is missing');
  }

  /**
   * One-on-one inbox happy path: the recipient has a profile.
   *
   * The ViewBuilder is invoked twice (once for $members_string in the loop
   * and once for $display_name in the one-on-one branch).
   *
   * @covers ::entityViewAlter
   */
  public function testInboxOneOnOneWithValidProfileRendersIt(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);

    $this->currentUser->method('id')->willReturn('101');

    // Map loadByUser by recipient so the test fails if the hook ever resolves
    // the wrong user. Alice is the current user and the alice profile must
    // never be loaded (alice is unset from the loop before loadByUser is
    // reached).
    $bob_profile = $this->createMock(ProfileInterface::class);
    $this->profileStorage->method('loadByUser')->willReturnMap([
      [$bob, 'profile', $bob_profile],
      [$alice, 'profile', NULL],
    ]);

    // ViewBuilder is invoked twice on the happy path: once inside the loop
    // for $members_string, and once for $display_name in the one-on-one
    // branch. compact_notification is only called if recipient instanceof
    // User (concrete class) — bob is a UserInterface mock so that branch is
    // skipped.
    $this->profileViewBuilder->expects($this->exactly(2))
      ->method('view')
      ->with($bob_profile, 'name_raw')
      ->willReturn(['#markup' => 'Bob']);

    $thread = $this->mockThread([$alice, $bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    // The one-on-one branch populates $build['membernames'] from $display_name
    // independently of $profile_picture, so the rendered recipient must land
    // in the build.
    $this->assertArrayHasKey('membernames', $build);
    $this->assertSame('Bob', (string) $build['membernames']['#markup']);
    $this->assertSame('<strong>', $build['membernames']['#prefix']);
    $this->assertSame('</strong>', $build['membernames']['#suffix']);
  }

  /**
   * Inbox: a deleted reference (no member entity for a field row).
   *
   * The members field has a row whose entity reference resolves to NULL, so
   * getMembers() returns []. This exercises two branches at once: the loop's
   * "Deleted user" fallback when isset($only_exist_members[$key]) is FALSE
   * (line 80 in the hook), and the one-on-one fallback when
   * end($only_exist_members) is not a UserInterface (lines 100-106 in the
   * hook), which then loads user 1.
   *
   * @covers ::entityViewAlter
   */
  public function testInboxDeletedMemberFallsBackAndLoadsUserOne(): void {
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->createMock(PrivateMessageThreadInterface::class);
    $thread->method('getEntityTypeId')->willReturn('private_message_thread');
    $thread->method('getMembers')->willReturn([]);

    $members_field = $this->createMock(FieldItemListInterface::class);
    $members_field->method('getValue')->willReturn([['target_id' => '999']]);
    $thread->method('get')->with('members')->willReturn($members_field);

    // No profile is loaded or rendered on this path: the recipient resolves
    // to NULL via the user-1 storage fallback, which is not a User instance.
    $this->profileStorage->expects($this->never())->method('loadByUser');
    $this->profileViewBuilder->expects($this->never())->method('view');

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertArrayHasKey('membernames', $build);
    $this->assertSame('Deleted user', (string) $build['membernames']['#markup']);
    $this->assertSame('<strong>', $build['membernames']['#prefix']);
    $this->assertSame('</strong>', $build['membernames']['#suffix']);
    $this->assertArrayNotHasKey('members', $build, 'no profile picture when recipient resolves to user 1 with no profile');
  }

  /**
   * Inbox multi-member thread with an orphan member reference.
   *
   * Reproduces PROD-35298: a thread member exists in the field value but is
   * absent from getMembers() (the referenced user was hard-deleted). The
   * loop's "Deleted user" fallback (line 80 of the hook) must produce a
   * render array — NOT a bare TranslatableMarkup object — otherwise
   * Element::children() throws
   * '"{key}" is an invalid render array key. Value should be an array but
   * got a object.' when Drupal renders $build['membernames'].
   *
   * @covers ::entityViewAlter
   */
  public function testInboxMultiMemberWithOrphanReferenceProducesRenderArrayOnly(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);
    $this->currentUser->method('id')->willReturn('101');

    $bob_profile = $this->createMock(ProfileInterface::class);
    $this->profileStorage->method('loadByUser')->willReturn($bob_profile);
    $this->profileViewBuilder->expects($this->once())
      ->method('view')
      ->with($bob_profile, 'name_raw')
      ->willReturn(['#markup' => 'Bob']);

    // Field has 3 entries (alice, bob, ghost@999) but getMembers() only
    // resolves [alice, bob] — the ghost row triggers the line-80 branch.
    $thread = $this->createMock(PrivateMessageThreadInterface::class);
    $thread->method('getEntityTypeId')->willReturn('private_message_thread');
    $thread->method('getMembers')->willReturn([0 => $alice, 1 => $bob]);

    $members_field = $this->createMock(FieldItemListInterface::class);
    $members_field->method('getValue')->willReturn([
      0 => ['target_id' => '101'],
      1 => ['target_id' => '102'],
      2 => ['target_id' => '999'],
    ]);
    $thread->method('get')->with('members')->willReturn($members_field);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    // Element::children() validates every numeric-keyed child of a render
    // array — bare objects there throw InvalidArgumentException at render
    // time. Walk the children and assert each one is an array.
    $this->assertArrayHasKey('membernames', $build);
    foreach ($build['membernames'] as $key => $value) {
      if (is_int($key)) {
        $this->assertIsArray(
          $value,
          sprintf('Child at numeric key "%s" must be a render array — regression of PROD-35298.', $key),
        );
      }
    }

    $rendered = array_values(array_filter($build['membernames'], 'is_array'));
    $this->assertCount(2, $rendered, 'bob (rendered) + ghost (deleted-user fallback)');
    $this->assertSame(['#markup' => 'Bob'], $rendered[0]);
    $this->assertArrayHasKey('#markup', $rendered[1]);
    $this->assertSame('Deleted user', (string) $rendered[1]['#markup']);
  }

  /**
   * Inbox one-on-one happy path with a concrete User instance.
   *
   * TestInboxOneOnOneWithValidProfileRendersIt uses a UserInterface mock,
   * which trips the instanceof UserInterface branch but NOT the
   * instanceof User (concrete) branch. This test uses a User mock so the
   * concrete-class branch (lines 109-114) — which loads the
   * compact_notification view mode and populates $build['members'] — runs.
   *
   * @covers ::entityViewAlter
   */
  public function testInboxOneOnOneWithConcreteUserRendersProfilePicture(): void {
    $alice = $this->mockUser(101);
    $bob = $this->createMock(User::class);
    $bob->method('id')->willReturn('102');

    $this->currentUser->method('id')->willReturn('101');

    $bob_profile = $this->createMock(ProfileInterface::class);
    $this->profileStorage->method('loadByUser')->willReturn($bob_profile);

    // view() is called three times on the concrete-User happy path:
    // 1. name_raw inside the loop ($members_string),
    // 2. name_raw for $display_name,
    // 3. compact_notification for $profile_picture.
    $this->profileViewBuilder->expects($this->exactly(3))
      ->method('view')
      ->willReturnCallback(function (ProfileInterface $profile, string $view_mode) use ($bob_profile) {
        $this->assertSame($bob_profile, $profile);
        return $view_mode === 'compact_notification'
          ? ['#markup' => 'Bob Avatar']
          : ['#markup' => 'Bob'];
      });

    $thread = $this->mockThread([$alice, $bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertSame(['#markup' => 'Bob Avatar'], $build['members']);
    $this->assertSame('Bob', (string) $build['membernames']['#markup']);
    $this->assertSame('<strong>', $build['membernames']['#prefix']);
    $this->assertSame('</strong>', $build['membernames']['#suffix']);
  }

  /**
   * Inbox: a thread whose only member is the current user.
   *
   * After the loop unsets the current user, $member_count is 0, so neither
   * the member_count === 1 nor the member_count > 1 branch runs, and the
   * empty checks in the else block both fail. $build must be untouched.
   *
   * @covers ::entityViewAlter
   */
  public function testInboxSingleMemberThreadOfCurrentUserLeavesBuildUntouched(): void {
    $alice = $this->mockUser(101);
    $this->currentUser->method('id')->willReturn('101');

    $this->profileStorage->expects($this->never())->method('loadByUser');
    $this->profileViewBuilder->expects($this->never())->method('view');

    $thread = $this->mockThread([$alice]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'inbox'];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertArrayNotHasKey('members', $build);
    $this->assertArrayNotHasKey('membernames', $build);
  }

  /**
   * Full view mode: delegates the check-time update to the service.
   *
   * @covers ::entityViewAlter
   */
  public function testFullViewModeUpdatesLastThreadCheckTime(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->mockThreadEntity([$alice, $bob]);

    $this->socialPrivateMessageService->expects($this->once())
      ->method('updateLastThreadCheckTime')
      ->with($thread);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'full'];

    $this->hook->entityViewAlter($build, $thread, $display);
  }

  /**
   * Full view mode: clears the build's #prefix and #suffix wrappers.
   *
   * @covers ::entityViewAlter
   */
  public function testFullViewModeClearsPrefixAndSuffix(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->mockThreadEntity([$alice, $bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $build = [
      '#view_mode' => 'full',
      '#prefix' => '<div class="wrapper">',
      '#suffix' => '</div>',
    ];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertSame('', $build['#prefix']);
    $this->assertSame('', $build['#suffix']);
  }

  /**
   * Full view + single-member thread + display has the component.
   *
   * The hook must null out $build['private_message_form'].
   *
   * @covers ::entityViewAlter
   */
  public function testFullViewModeRemovesPrivateMessageFormForSingleMemberThread(): void {
    $bob = $this->mockUser(102);
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->mockThreadEntity([$bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')
      ->with('private_message_form')
      ->willReturn(['type' => 'private_message_thread_form']);

    $build = [
      '#view_mode' => 'full',
      'private_message_form' => ['#markup' => 'the form'],
    ];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertArrayHasKey('private_message_form', $build);
    $this->assertNull($build['private_message_form']);
  }

  /**
   * Full view + multi-member thread: private_message_form is preserved.
   *
   * The condition in the hook is count($only_exist_members) === 1; with two
   * or more thread members the form must be untouched even when the display
   * exposes the component.
   *
   * @covers ::entityViewAlter
   */
  public function testFullViewModePreservesPrivateMessageFormForMultiMemberThread(): void {
    $alice = $this->mockUser(101);
    $bob = $this->mockUser(102);
    $charlie = $this->mockUser(103);
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->mockThreadEntity([$alice, $bob, $charlie]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')
      ->with('private_message_form')
      ->willReturn(['type' => 'private_message_thread_form']);

    $build = [
      '#view_mode' => 'full',
      'private_message_form' => ['#markup' => 'the form'],
    ];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertSame(
      ['#markup' => 'the form'],
      $build['private_message_form'],
    );
  }

  /**
   * Full view + display lacks the component: private_message_form is preserved.
   *
   * @covers ::entityViewAlter
   */
  public function testFullViewModePreservesPrivateMessageFormWhenComponentMissing(): void {
    $bob = $this->mockUser(102);
    $this->currentUser->method('id')->willReturn('101');

    $thread = $this->mockThreadEntity([$bob]);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')
      ->with('private_message_form')
      ->willReturn(NULL);

    $build = [
      '#view_mode' => 'full',
      'private_message_form' => ['#markup' => 'the form'],
    ];

    $this->hook->entityViewAlter($build, $thread, $display);

    $this->assertSame(
      ['#markup' => 'the form'],
      $build['private_message_form'],
    );
  }

  /**
   * Build a PrivateMessageInterface mock owned by the given uid.
   *
   * The hook compares owner against the current user with strict ===, and
   * AccountProxy::id() returns a string in Drupal, so the mock returns a
   * string here to mirror what the comparison actually receives at runtime.
   */
  protected function mockPrivateMessage(int $owner_id): PrivateMessageInterface {
    $message = $this->createMock(PrivateMessageInterface::class);
    $message->method('getEntityTypeId')->willReturn('private_message');
    $message->method('getOwnerId')->willReturn((string) $owner_id);
    return $message;
  }

  /**
   * Private message default view: current user is the owner → "You" label.
   *
   * @covers ::entityViewAlter
   */
  public function testPrivateMessageDefaultViewReplacesOwnerWithYouWhenCurrentUserIsOwner(): void {
    $this->currentUser->method('id')->willReturn('101');
    $message = $this->mockPrivateMessage(101);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [
      '#view_mode' => 'default',
      'owner' => [0 => ['#plain_text' => 'Alice']],
    ];

    $this->hook->entityViewAlter($build, $message, $display);

    $this->assertArrayHasKey('owner', $build);
    $this->assertSame('You', (string) $build['owner'][0]['#plain_text']);
  }

  /**
   * Private message default view: another user owns it → label is untouched.
   *
   * @covers ::entityViewAlter
   */
  public function testPrivateMessageDefaultViewPreservesOwnerWhenCurrentUserIsNotOwner(): void {
    $this->currentUser->method('id')->willReturn('101');
    $message = $this->mockPrivateMessage(102);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [
      '#view_mode' => 'default',
      'owner' => [0 => ['#plain_text' => 'Bob']],
    ];
    $original = $build;

    $this->hook->entityViewAlter($build, $message, $display);

    $this->assertSame($original, $build, 'owner must be untouched when current user is not the owner');
  }

  /**
   * Private message non-default view mode: the "You" branch must not run.
   *
   * @covers ::entityViewAlter
   */
  public function testPrivateMessageNonDefaultViewModeIsNoop(): void {
    $this->currentUser->method('id')->willReturn('101');
    $message = $this->mockPrivateMessage(101);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [
      '#view_mode' => 'teaser',
      'owner' => [0 => ['#plain_text' => 'Alice']],
    ];
    $original = $build;

    $this->hook->entityViewAlter($build, $message, $display);

    $this->assertSame($original, $build, 'non-default PM view modes must leave $build untouched');
  }

  /**
   * The hook must be a no-op for unrelated entity types.
   *
   * Anything that is neither private_message_thread nor private_message
   * must leave $build untouched.
   *
   * @covers ::entityViewAlter
   */
  public function testIgnoresUnrelatedEntities(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    $this->profileStorage->expects($this->never())->method('loadByUser');
    $this->profileViewBuilder->expects($this->never())->method('view');

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = ['#view_mode' => 'full'];
    $original = $build;

    $this->hook->entityViewAlter($build, $entity, $display);

    $this->assertSame($original, $build, '$build must be untouched for non-PM entities');
  }

}
