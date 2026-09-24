<?php

declare(strict_types=1);

namespace Drupal\Tests\activity_basics\Unit\Plugin\ActivityContext;

use Drupal\activity_basics\Plugin\ActivityContext\ContentInMyGroupActivityContext;
use Drupal\activity_creator\ActivityFactory;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\media\MediaInterface;
use Drupal\message\MessageInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\CrossPostingService;
use Drupal\social_group\GroupMuteNotify;
use Drupal\social_post\Entity\PostInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the recipients of content posted in a group.
 *
 * The tests drive the plugin through a small in-memory model of the group
 * relationships of a node, the messages those relationships carry and the
 * membership of every group. The partition that keeps a single notification
 * per reader is therefore exercised through the query shapes the plugin
 * really builds, instead of being asserted against a mock of itself.
 *
 * @group activity_basics
 *
 * @coversDefaultClass \Drupal\activity_basics\Plugin\ActivityContext\ContentInMyGroupActivityContext
 */
final class ContentInMyGroupActivityContextTest extends UnitTestCase {

  /**
   * The message template every activity in these tests is created for.
   */
  private const TEMPLATE = 'create_content_in_joined_group';

  /**
   * A second template built on the same activity context.
   *
   * The social_challenge module notifies the members of a joined challenge
   * through the very same plugin, so the partition applies to it as well. It is
   * the only other template of this context whose group relationships wrap a
   * node: the remaining ones wrap a media item or a taxonomy term.
   */
  private const CHALLENGE_TEMPLATE = 'create_idea_in_joined_challenge';

  /**
   * The template the queue item being processed was created for.
   */
  private string $template = self::TEMPLATE;

  /**
   * The template of the message a relationship carries, per relationship id.
   *
   * A relationship listed in $relationshipsWithMessage but absent here carries
   * a message of self::TEMPLATE.
   *
   * @var array<int, string>
   */
  private array $templatePerRelationship = [];

  /**
   * The group of every relationship of the node, keyed by relationship id.
   *
   * The group ids are written as ints for readability, but the lookup they
   * stand in for returns strings, so some cases use strings on purpose.
   *
   * @var array<int, int|string>
   */
  private array $relationships = [];

  /**
   * The relationships that already carry a message of the template.
   *
   * @var int[]
   */
  private array $relationshipsWithMessage = [];

  /**
   * The relationships whose group reference is empty.
   *
   * @var int[]
   */
  private array $relationshipsWithoutGroup = [];

  /**
   * The members of every group, as user ids keyed by group id.
   *
   * The user id 0 stands for a membership whose user no longer exists.
   *
   * @var array<int, int[]>
   */
  private array $groupMembers = [];

  /**
   * The "group id:user id" pairs for which notifications are muted.
   *
   * @var string[]
   */
  private array $mutedPairs = [];

  /**
   * The ids of the users that cannot view the content.
   *
   * @var int[]
   */
  private array $withoutViewAccess = [];

  /**
   * The ids of the groups that were deleted after their relationship was read.
   *
   * @var int[]
   */
  private array $missingGroups = [];

  /**
   * The owner of the content.
   */
  private int $ownerId = 7;

  /**
   * Whether the content is published.
   */
  private bool $published = TRUE;

  /**
   * Whether loading a storage throws, as when an entity type is uninstalled.
   */
  private bool $storageIsMissing = FALSE;

  /**
   * The related object the activity factory resolves the queue item to.
   *
   * @var array<string, mixed>
   */
  private array $relatedObject = [];

  /**
   * The cross-posting service mock.
   *
   * @var \Drupal\social_group\CrossPostingService&\PHPUnit\Framework\MockObject\MockObject
   */
  private CrossPostingService&MockObject $crossPostingService;

  /**
   * The group mute notification service mock.
   *
   * @var \Drupal\social_group\GroupMuteNotify&\PHPUnit\Framework\MockObject\MockObject
   */
  private GroupMuteNotify&MockObject $groupMuteNotify;

  /**
   * The activity factory mock.
   *
   * @var \Drupal\activity_creator\ActivityFactory&\PHPUnit\Framework\MockObject\MockObject
   */
  private ActivityFactory&MockObject $activityFactory;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The group relationship storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $relationshipStorage;

  /**
   * The message storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $messageStorage;

  /**
   * The group storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupStorage;

  /**
   * The post storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $postStorage;

  /**
   * The database connection mock.
   *
   * @var \Drupal\Core\Database\Connection&\PHPUnit\Framework\MockObject\MockObject
   */
  private Connection&MockObject $database;

  /**
   * The post a post related object resolves to.
   *
   * @var \Drupal\social_post\Entity\PostInterface|null
   */
  private ?PostInterface $post = NULL;

  /**
   * The user accounts of every member, keyed by user id.
   *
   * @var \Drupal\user\UserInterface[]
   */
  private array $accounts = [];

  /**
   * The group entities, keyed by group id.
   *
   * @var \Drupal\group\Entity\GroupInterface[]
   */
  private array $groups = [];

  /**
   * The entity the group relationships wrap.
   */
  private ContentEntityInterface $relatedEntity;

  /**
   * The conditions every membership lookup was given, in order.
   *
   * @var array<int, array<string, mixed>>
   */
  private array $membershipQueries = [];

  /**
   * The cross-posting settings the plugin reads.
   *
   * Cross-posting enabled and notifying every group is the state in which the
   * deduplication exists at all, so it is the default for these cases.
   *
   * @var array<string, mixed>
   */
  private array $crossPosting = [
    'status' => TRUE,
    'notify_all_groups' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->crossPostingService = $this->createMock(CrossPostingService::class);
    $this->groupMuteNotify = $this->createMock(GroupMuteNotify::class);
    $this->activityFactory = $this->createMock(ActivityFactory::class);

    $this->relationshipStorage = $this->createMock(EntityStorageInterface::class);
    $this->messageStorage = $this->createMock(EntityStorageInterface::class);
    $this->groupStorage = $this->createMock(EntityStorageInterface::class);
    $this->postStorage = $this->createMock(EntityStorageInterface::class);
    $this->database = $this->createMock(Connection::class);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->willReturnCallback(
      function (string $entity_type_id): EntityStorageInterface {
        if ($this->storageIsMissing) {
          throw new PluginNotFoundException($entity_type_id);
        }

        return match ($entity_type_id) {
          'group_content' => $this->relationshipStorage,
          'message' => $this->messageStorage,
          'group' => $this->groupStorage,
          'post' => $this->postStorage,
          default => throw new \LogicException('Unexpected storage: ' . $entity_type_id),
        };
      }
    );

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(42);
    $node->method('getOwnerId')->willReturnCallback(fn (): int => $this->ownerId);
    $node->method('isPublished')->willReturnCallback(fn (): bool => $this->published);
    $node->method('access')->willReturnCallback(
      fn (string $operation, ?AccountInterface $account = NULL): bool => $account !== NULL
        && !in_array((int) $account->id(), $this->withoutViewAccess, TRUE)
    );
    $this->relatedEntity = $node;

    $this->activityFactory->method('getActivityRelatedEntity')
      ->willReturnCallback(fn (): array => $this->relatedObject);

    $this->groupMuteNotify->method('groupNotifyIsMuted')->willReturnCallback(
      fn (GroupInterface $group, AccountInterface $account): bool => in_array(
        $group->id() . ':' . $account->id(),
        $this->mutedPairs,
        TRUE
      )
    );

    $this->crossPostingService->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck')
      ->willReturnCallback(fn (): array => $this->relationships);

    $this->wireStorages();
  }

  /**
   * Builds the plugin under test.
   */
  private function plugin(): ContentInMyGroupActivityContext {
    return new ContentInMyGroupActivityContext(
      [],
      'content_in_my_group_activity_context',
      [],
      $this->createMock(QueryFactory::class),
      $this->entityTypeManager,
      $this->activityFactory,
      $this->groupMuteNotify,
      $this->crossPostingService,
      $this->database,
      $this->configFactory()
    );
  }

  /**
   * Returns a config factory answering the cross-posting settings.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   A config factory whose social_group.settings carries the cross-posting
   *   values the current test case set up.
   */
  private function configFactory(): ConfigFactoryInterface {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(
      fn (string $key): mixed => $key === 'cross_posting' ? $this->crossPosting : NULL
    );

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturnCallback(
      fn (string $name): ImmutableConfig => $config
    );

    return $config_factory;
  }

  /**
   * Returns an account mock for a user id, creating it on first use.
   */
  private function account(int $uid): UserInterface {
    if (!isset($this->accounts[$uid])) {
      $account = $this->createMock(UserInterface::class);
      $account->method('id')->willReturn($uid);
      $this->accounts[$uid] = $account;
    }

    return $this->accounts[$uid];
  }

  /**
   * Returns a stand-in for one of the memberships a group hands out.
   *
   * \Drupal\group\Entity\Group::getMembers() returns instances of
   * \Drupal\group\GroupMembership, which is deprecated in group:3.2.0. The
   * bundle class that replaces it exposes no getUser(), so mocking either one
   * would either name a deprecated class or miss the only method the plugin
   * calls. This double exposes exactly that method instead.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The member, or NULL for a membership whose user no longer exists.
   *
   * @return object
   *   A membership exposing the getUser() the plugin calls.
   */
  private function membership(?UserInterface $user): object {
    return new class($user) {

      /**
       * Constructs the membership stand-in.
       *
       * @param \Drupal\user\UserInterface|null $user
       *   The member, or NULL for a membership whose user no longer exists.
       */
      public function __construct(protected ?UserInterface $user) {}

      /**
       * Returns the member this membership belongs to.
       *
       * @return \Drupal\user\UserInterface|null
       *   The member, or NULL when the user no longer exists.
       */
      public function getUser(): ?UserInterface {
        return $this->user;
      }

    };
  }

  /**
   * Returns a group mock for a group id, creating it on first use.
   */
  private function group(int $gid): GroupInterface {
    if (!isset($this->groups[$gid])) {
      $memberships = [];
      foreach ($this->groupMembers[$gid] ?? [] as $uid) {
        $memberships[] = $this->membership($uid === 0 ? NULL : $this->account($uid));
      }

      $group = $this->createMock(GroupInterface::class);
      $group->method('id')->willReturn($gid);
      $group->method('getMembers')->willReturn($memberships);
      $this->groups[$gid] = $group;
    }

    return $this->groups[$gid];
  }

  /**
   * Builds a field item list mock yielding a single entity reference value.
   *
   * @param int|string|null $target_id
   *   The referenced id, or NULL for an empty reference. Entity references
   *   read back as strings often enough that both are accepted here.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface>
   *   The field item list the entity returns for that reference.
   */
  private function referenceField(int|string|null $target_id): FieldItemListInterface {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('getValue')
      ->willReturn($target_id === NULL ? [] : [['target_id' => $target_id]]);

    return $field;
  }

  /**
   * Wires the storages that back the relationship, message and group lookups.
   */
  private function wireStorages(): void {
    $this->postStorage->method('load')->willReturnCallback(fn (): ?PostInterface => $this->post);

    $this->relationshipStorage->method('load')->willReturnCallback(
      function (int|string $id): ?GroupRelationshipInterface {
        $id = (int) $id;
        if (!isset($this->relationships[$id])) {
          return NULL;
        }

        $group_reference = in_array($id, $this->relationshipsWithoutGroup, TRUE)
          ? $this->referenceField(NULL)
          : $this->referenceField($this->relationships[$id]);

        $relationship = $this->createMock(GroupRelationshipInterface::class);
        $relationship->method('id')->willReturn($id);
        $relationship->method('getEntity')->willReturn($this->relatedEntity);
        $relationship->method('get')->with('gid')->willReturn($group_reference);

        return $relationship;
      }
    );

    $this->messageStorage->method('getQuery')->willReturnCallback(
      fn (): QueryInterface => $this->recordingQuery(
        fn (array $conditions): array => $this->messageIds($conditions)
      )
    );
    $this->messageStorage->method('loadMultiple')->willReturnCallback(
      function (array $ids): array {
        $messages = [];
        foreach ($ids as $id) {
          $field = $this->createMock(FieldItemListInterface::class);
          $field->method('getValue')->willReturn([
            ['target_id' => (int) $id, 'target_type' => 'group_content'],
          ]);

          $message = $this->createMock(MessageInterface::class);
          $message->method('get')->with('field_message_related_object')->willReturn($field);
          $messages[$id] = $message;
        }

        return $messages;
      }
    );

    $this->database->method('select')->willReturnCallback(
      function (string $table, string $alias): SelectInterface {
        $this->assertSame('group_relationship_field_data', $table);
        $this->assertSame('gc', $alias);

        return $this->membershipSelect();
      }
    );

    $this->groupStorage->method('load')->willReturnCallback(
      fn (int|string $gid): ?GroupInterface => in_array((int) $gid, $this->missingGroups, TRUE)
        ? NULL
        : $this->group((int) $gid)
    );
    $this->groupStorage->method('loadMultiple')->willReturnCallback(
      function (array $gids): array {
        $groups = [];
        foreach ($gids as $gid) {
          if (!in_array((int) $gid, $this->missingGroups, TRUE)) {
            $groups[(int) $gid] = $this->group((int) $gid);
          }
        }

        return $groups;
      }
    );
  }

  /**
   * Builds an entity query mock that records the conditions it is given.
   *
   * @param callable $resolve
   *   Receives the recorded conditions and returns the matching entity ids.
   */
  private function recordingQuery(callable $resolve): QueryInterface {
    $conditions = [];

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnCallback(
      function (bool $access_check = TRUE) use (&$conditions, $query): QueryInterface {
        $conditions['#access_check'] = $access_check;
        return $query;
      }
    );
    $query->method('condition')->willReturnCallback(
      function (string $field, $value = NULL, $operator = NULL) use (&$conditions, $query): QueryInterface {
        $conditions[$field] = $value;
        return $query;
      }
    );
    // A regular closure, because an arrow function would capture the
    // conditions by value and therefore always see them empty.
    $query->method('execute')->willReturnCallback(
      function () use (&$conditions, $resolve): array {
        return $resolve($conditions);
      }
    );

    return $query;
  }

  /**
   * Resolves the message lookup against the in-memory model.
   *
   * The returned ids double as the relationship ids the messages point at, so
   * that loadMultiple() can hand back one message per relationship.
   *
   * @param array $conditions
   *   The conditions the plugin put on the query.
   *
   * @return int[]
   *   The ids of the matching messages.
   */
  private function messageIds(array $conditions): array {
    $this->assertFalse($conditions['#access_check'], 'The message lookup runs without an access check.');
    $this->assertSame($this->template, $conditions['template'], 'The message lookup is scoped to the template of the activity being built.');
    $this->assertSame('group_content', $conditions['field_message_related_object.target_type']);

    $matching = [];
    foreach (array_intersect($this->relationshipsWithMessage, $conditions['field_message_related_object.target_id']) as $relationship_id) {
      $template = $this->templatePerRelationship[$relationship_id] ?? self::TEMPLATE;

      if ($template === $conditions['template']) {
        $matching[] = $relationship_id;
      }
    }

    return $matching;
  }

  /**
   * Builds the select the membership lookup runs, backed by the model.
   *
   * The statement yields the two columns the plugin reads, so the rows are
   * the same shape the database hands back rather than entities.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A select recording its fields and conditions.
   */
  private function membershipSelect(): SelectInterface {
    $fields = [];
    $conditions = [];

    $query = $this->createMock(SelectInterface::class);
    $query->method('addField')->willReturnCallback(
      function (string $table, string $field) use (&$fields, $query): SelectInterface {
        $fields[] = $table . '.' . $field;
        return $query;
      }
    );
    $query->method('condition')->willReturnCallback(
      function (string $field, $value = NULL, $operator = NULL) use (&$conditions, $query): SelectInterface {
        $conditions[$field] = $value;
        return $query;
      }
    );
    // A regular closure, because an arrow function would capture the fields
    // and conditions by value and therefore always see them empty.
    $query->method('execute')->willReturnCallback(
      function () use (&$fields, &$conditions): \Iterator {
        $this->assertSame(['gc.gid', 'gc.entity_id'], $fields);
        $this->assertSame('group_membership', $conditions['gc.plugin_id']);
        $this->membershipQueries[] = [
          'gid' => $conditions['gc.gid'],
          'entity_id' => $conditions['gc.entity_id'],
        ];

        $rows = [];
        foreach ($conditions['gc.gid'] as $gid) {
          foreach ($this->groupMembers[(int) $gid] ?? [] as $uid) {
            if (in_array($uid, $conditions['gc.entity_id'], TRUE)) {
              $rows[] = (object) ['gid' => (string) $gid, 'entity_id' => (string) $uid];
            }
          }
        }

        return new \ArrayIterator($rows);
      }
    );

    return $query;
  }

  /**
   * Runs the plugin for one group relationship.
   *
   * @param int $relationship_id
   *   The relationship the activity is built for.
   * @param bool $with_template
   *   Whether the queue item carries the message template.
   *
   * @return array
   *   The recipients the plugin resolved.
   */
  private function recipientsFor(int $relationship_id, bool $with_template = TRUE): array {
    $this->relatedObject = [
      'target_type' => 'group_content',
      'target_id' => $relationship_id,
    ];

    $data = ['related_object' => [['target_id' => $relationship_id]]];
    if ($with_template) {
      $data['message_template'] = $this->template;
    }

    return $this->plugin()->getRecipients($data, 0, 0);
  }

  /**
   * Runs the plugin for one relationship and returns the notified user ids.
   *
   * @param int $relationship_id
   *   The relationship the activity is built for.
   * @param bool $with_template
   *   Whether the queue item carries the message template.
   *
   * @return int[]
   *   The ids of the users the activity is addressed to.
   */
  private function notifiedUids(int $relationship_id, bool $with_template = TRUE): array {
    $uids = [];
    foreach ($this->recipientsFor($relationship_id, $with_template) as $recipient) {
      if ($recipient['target_type'] === 'user') {
        $uids[] = (int) $recipient['target_id'];
      }
    }

    return $uids;
  }

  /**
   * A queue item without a related object yields no recipient at all.
   *
   * @covers ::getRecipients
   */
  public function testWithoutRelatedObjectThereAreNoRecipients(): void {
    $this->assertSame([], $this->plugin()->getRecipients([], 0, 0));
    $this->assertSame([], $this->plugin()->getRecipients(['related_object' => []], 0, 0));
  }

  /**
   * A post notifies its group without ever consulting the partition.
   *
   * Posts reach their group through a field instead of a group relationship,
   * so they are never cross-posted and must not be partitioned.
   *
   * @covers ::getRecipients
   */
  public function testPostNotifiesItsGroupWithoutPartitioning(): void {
    $this->groupMembers = [1 => [5, 6]];

    $post = $this->createMock(PostInterface::class);
    $post->method('get')->with('field_recipient_group')->willReturn($this->referenceField(1));
    $post->method('getOwnerId')->willReturn(6);
    $post->method('access')->willReturn(TRUE);
    $this->post = $post;

    $this->crossPostingService->expects($this->never())
      ->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck');
    $this->messageStorage->expects($this->never())->method('getQuery');

    $this->relatedObject = ['target_type' => 'post', 'target_id' => 99];

    $this->assertSame(
      [
        ['target_type' => 'group', 'target_id' => 1],
        ['target_type' => 'user', 'target_id' => 5],
      ],
      $this->plugin()->getRecipients([
        'related_object' => [['target_id' => 99]],
        'message_template' => self::TEMPLATE,
      ], 0, 0)
    );
  }

  /**
   * An uninstalled post entity type yields no recipient.
   *
   * @covers ::getRecipients
   */
  public function testMissingPostEntityTypeYieldsNoRecipients(): void {
    $this->storageIsMissing = TRUE;
    $this->relatedObject = ['target_type' => 'post', 'target_id' => 99];

    $this->assertSame([], $this->plugin()->getRecipients([
      'related_object' => [['target_id' => 99]],
    ], 0, 0));
  }

  /**
   * A post deleted while its notification was queued yields no recipient.
   *
   * @covers ::getRecipients
   */
  public function testDeletedPostYieldsNoRecipients(): void {
    $this->post = NULL;
    $this->relatedObject = ['target_type' => 'post', 'target_id' => 99];

    $this->assertSame([], $this->plugin()->getRecipients([
      'related_object' => [['target_id' => 99]],
    ], 0, 0));
  }

  /**
   * A relationship deleted while its notification was queued notifies nobody.
   *
   * @covers ::getRecipients
   */
  public function testDeletedRelationshipYieldsNoRecipients(): void {
    $this->relationships = [];

    $this->assertSame([], $this->recipientsFor(10));
  }

  /**
   * Unpublished content notifies nobody.
   *
   * @covers ::getRecipients
   */
  public function testUnpublishedContentYieldsNoRecipients(): void {
    $this->published = FALSE;
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->groupMembers = [1 => [5]];

    $this->assertSame([], $this->recipientsFor(10));
  }

  /**
   * A relationship without a group notifies nobody.
   *
   * @covers ::getRecipients
   */
  public function testRelationshipWithoutGroupYieldsNoRecipients(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithoutGroup = [10];
    $this->groupMembers = [1 => [5]];

    $this->assertSame([], $this->recipientsFor(10));
  }

  /**
   * A group deleted while its notification was queued keeps the group only.
   *
   * @covers ::getRecipients
   */
  public function testDeletedGroupYieldsOnlyTheGroupRecipient(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->missingGroups = [1];

    $this->assertSame(
      [['target_type' => 'group', 'target_id' => 1]],
      $this->recipientsFor(10)
    );
  }

  /**
   * Content whose entity has no owner still notifies the group members.
   *
   * Such an entity is not a node either, so the partition stays out of it.
   *
   * @covers ::getRecipients
   */
  public function testEntityWithoutOwnerNotifiesEveryMember(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('access')->willReturn(TRUE);
    $this->relatedEntity = $entity;

    $this->crossPostingService->expects($this->never())
      ->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck');

    $this->assertSame([5], $this->notifiedUids(11));
  }

  /**
   * The author of the content is never notified, in any of its groups.
   *
   * @covers ::getRecipients
   */
  public function testAuthorIsNeverNotified(): void {
    $this->ownerId = 5;
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    $this->assertSame([6], $this->notifiedUids(10));
    $this->assertSame([8], $this->notifiedUids(11));
  }

  /**
   * A member who muted the group is not notified for it.
   *
   * @covers ::getRecipients
   */
  public function testMutedMemberIsNotNotified(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->groupMembers = [1 => [5, 6]];
    $this->mutedPairs = ['1:6'];

    $this->assertSame([5], $this->notifiedUids(10));
  }

  /**
   * A member who cannot view the content is not notified.
   *
   * @covers ::getRecipients
   */
  public function testMemberWithoutViewAccessIsNotNotified(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->groupMembers = [1 => [5, 6]];
    $this->withoutViewAccess = [6];

    $this->assertSame([5], $this->notifiedUids(10));
  }

  /**
   * A membership left behind by a deleted user is skipped.
   *
   * @covers ::getRecipients
   */
  public function testMembershipOfDeletedUserIsSkipped(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->groupMembers = [1 => [0, 5]];

    $this->assertSame([5], $this->notifiedUids(10));
  }

  /**
   * A group without a single member asks nothing of the partition.
   *
   * @covers ::getRecipients
   */
  public function testGroupWithoutMembersSkipsThePartition(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => []];

    $this->crossPostingService->expects($this->never())
      ->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck');

    $this->assertSame([], $this->notifiedUids(11));
  }

  /**
   * Content in a single group is never partitioned.
   *
   * @covers ::getRecipients
   */
  public function testSingleGroupContentIssuesNoPartitionQuery(): void {
    $this->relationships = [10 => 1];
    $this->relationshipsWithMessage = [10];
    $this->groupMembers = [1 => [5, 6]];

    $this->messageStorage->expects($this->never())->method('getQuery');

    $this->assertSame([5, 6], $this->notifiedUids(10));
  }

  /**
   * A queue item without a message template is never partitioned.
   *
   * @covers ::getRecipients
   */
  public function testQueueItemWithoutTemplateIssuesNoPartitionQuery(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $this->crossPostingService->expects($this->never())
      ->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck');

    $this->assertSame([5], $this->notifiedUids(11, FALSE));
  }

  /**
   * Cross-posted content none of whose groups notifies suppresses nobody.
   *
   * @covers ::getRecipients
   */
  public function testCrossPostedContentWithoutAnyMessageSuppressesNobody(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $this->assertSame([5], $this->notifiedUids(11));
    $this->assertSame([], $this->membershipQueries);
  }

  /**
   * The lowest cross-posted group notifies without asking who is covered.
   *
   * @covers ::getRecipients
   */
  public function testLowestGroupNotifiesEveryMember(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5]];

    $this->assertSame([5, 6], $this->notifiedUids(10));
    $this->assertSame([], $this->membershipQueries, 'The lowest group needs no membership lookup.');
  }

  /**
   * A reader of two cross-posted groups is notified by the lower one only.
   *
   * @covers ::getRecipients
   */
  public function testReaderOfTwoGroupsIsNotifiedByTheLowerOneOnly(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    $this->assertEqualsCanonicalizing([5, 6], $this->notifiedUids(10));
    $this->assertEqualsCanonicalizing([8], $this->notifiedUids(11));
  }

  /**
   * Without the notify-all-groups setting nobody is suppressed.
   *
   * The deduplication only exists for the notifications that setting creates,
   * so with it off the method must return what it returned before the feature
   * existed: every member of every relationship.
   *
   * @covers ::getRecipients
   */
  public function testTheSettingOffSuppressesNobody(): void {
    $this->crossPosting = ['status' => TRUE, 'notify_all_groups' => FALSE];
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    // The same reader, 5, is notified by both relationships — the behaviour
    // testReaderOfTwoGroupsIsNotifiedByTheLowerOneOnly() asserts is replaced
    // by the partition once the setting is on.
    $this->assertEqualsCanonicalizing([5, 6], $this->notifiedUids(10));
    $this->assertEqualsCanonicalizing([5, 8], $this->notifiedUids(11));
  }

  /**
   * Cross-posting disabled altogether suppresses nobody either.
   *
   * Notify_all_groups cannot be reached with cross-posting off, but the two
   * keys are independent in configuration and a stale TRUE can outlive the
   * feature being switched off.
   *
   * @covers ::getRecipients
   */
  public function testCrossPostingDisabledSuppressesNobody(): void {
    $this->crossPosting = ['status' => FALSE, 'notify_all_groups' => TRUE];
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    $this->assertEqualsCanonicalizing([5, 6], $this->notifiedUids(10));
    $this->assertEqualsCanonicalizing([5, 8], $this->notifiedUids(11));
  }

  /**
   * With the setting off the dedup lookups are not run at all.
   *
   * The point of the guard is the cost, not only the behaviour: a site that
   * never enabled cross-post notifications must pay nothing for them. The
   * membership lookup is the first query the partition issues.
   *
   * @covers ::getRecipients
   */
  public function testTheSettingOffIssuesNoDedupQuery(): void {
    $this->crossPosting = ['status' => TRUE, 'notify_all_groups' => FALSE];
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    // Relationship 11 carries the higher group id, so it is the one that
    // would look up which of its members a lower group already covers.
    // Asking relationship 10 would prove nothing: it is first in the
    // ordering and returns before any query, guard or no guard.
    $this->notifiedUids(11);

    $this->assertSame([], $this->membershipQueries);
  }

  /**
   * The ordering follows the group id, not the order of the relationships.
   *
   * @covers ::getRecipients
   */
  public function testTheLowestGroupIdWinsWhateverTheRelationshipOrder(): void {
    // The relationship created first points at the higher group id.
    $this->relationships = [10 => 9, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [9 => [5], 2 => [5]];

    $this->assertSame([], $this->notifiedUids(10));
    $this->assertSame([5], $this->notifiedUids(11));
  }

  /**
   * Group ids are ordered as numbers even though the database returns strings.
   *
   * The lookup reads its group ids with fetchAllKeyed(), which yields strings.
   * Ordering those as text would put "10" before "9" and hand the reader to
   * the wrong group, so the ids here are strings on purpose and were chosen
   * to be the pair that tells the two orderings apart.
   *
   * @covers ::getRecipients
   */
  public function testGroupIdsAreOrderedNumericallyNotAsText(): void {
    $this->relationships = [10 => '9', 11 => '10'];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [9 => [5], 10 => [5]];

    $this->assertSame([5], $this->notifiedUids(10), 'Group 9 is the lower one.');
    $this->assertSame([], $this->notifiedUids(11), 'Group 10 is the higher one.');
  }

  /**
   * A reader who muted their lowest group is notified by the next one.
   *
   * Without this the reader would be dropped from the higher group in favour
   * of a notification their mute silences, and end up notified by nothing.
   *
   * @covers ::getRecipients
   */
  public function testReaderWhoMutedTheirLowestGroupIsNotifiedByTheNextOne(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => [5]];
    $this->mutedPairs = ['1:5'];

    $this->assertSame([], $this->notifiedUids(10), 'The muted group stays silent.');
    $this->assertSame([5], $this->notifiedUids(11), 'The next group takes over.');
  }

  /**
   * A muted group in the middle hands the reader to the next lower group.
   *
   * @covers ::getRecipients
   */
  public function testMutedLowerGroupHandsTheReaderToTheNextLowerOne(): void {
    $this->relationships = [10 => 1, 11 => 2, 12 => 3];
    $this->relationshipsWithMessage = [10, 11, 12];
    $this->groupMembers = [1 => [5], 2 => [5], 3 => [5]];
    $this->mutedPairs = ['1:5'];

    $this->assertSame([5], $this->notifiedUids(11));
    $this->assertSame([], $this->notifiedUids(12));
  }

  /**
   * A reader who muted every cross-posted group is notified by none of them.
   *
   * @covers ::getRecipients
   */
  public function testReaderWhoMutedEveryGroupIsNotifiedByNone(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => [5]];
    $this->mutedPairs = ['1:5', '2:5'];

    $this->assertSame([], $this->notifiedUids(10));
    $this->assertSame([], $this->notifiedUids(11));
  }

  /**
   * A lower group that notifies nobody suppresses nobody.
   *
   * Group-less content edited to join several groups at once only gets a
   * message for the relationship inserted first. Counting the other groups
   * would remove a reader from the only group that does notify them.
   *
   * @covers ::getRecipients
   */
  public function testLowerGroupWithoutMessageSuppressesNobody(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [11];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $this->assertSame([5], $this->notifiedUids(11));
    $this->assertSame([], $this->membershipQueries, 'A lone notifying group needs no lookup.');
  }

  /**
   * A relationship whose own message is gone suppresses nobody.
   *
   * @covers ::getRecipients
   */
  public function testRelationshipWithoutItsOwnMessageSuppressesNobody(): void {
    $this->relationships = [10 => 1, 11 => 2, 12 => 3];
    // The relationship being processed lost its message, the others kept one.
    $this->relationshipsWithMessage = [10, 12];
    $this->groupMembers = [1 => [5], 2 => [5], 3 => [5]];

    $this->assertSame([5], $this->notifiedUids(11));
  }

  /**
   * Content in three groups notifies a reader through their lowest group.
   *
   * @covers ::getRecipients
   */
  public function testReaderOfTheSecondAndThirdGroupIsNotifiedBySecondOnly(): void {
    $this->relationships = [10 => 1, 11 => 2, 12 => 3];
    $this->relationshipsWithMessage = [10, 11, 12];
    $this->groupMembers = [1 => [9], 2 => [5], 3 => [5]];

    $this->assertSame([5], $this->notifiedUids(11));
    $this->assertSame([], $this->notifiedUids(12));
  }

  /**
   * Membership of a group the content is not in never suppresses anything.
   *
   * @covers ::getRecipients
   */
  public function testMembershipOfAnUnrelatedGroupSuppressesNothing(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    // Group 4 holds the reader too, but the content was never posted there.
    $this->groupMembers = [1 => [9], 2 => [5], 4 => [5]];

    $this->assertSame([5], $this->notifiedUids(11));
    $this->assertSame(
      [1],
      $this->membershipQueries[0]['gid'],
      'Only the groups the content is in are looked up.'
    );
  }

  /**
   * The membership lookup is bounded by the members of the current group.
   *
   * @covers ::getRecipients
   */
  public function testTheMembershipLookupIsBoundedByTheCurrentGroup(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6, 7], 2 => [5]];

    $this->assertSame([], $this->notifiedUids(11));
    $this->assertSame(
      [5],
      $this->membershipQueries[0]['entity_id'],
      'Members of the lower group who are not in this one are never fetched.'
    );
  }

  /**
   * A lower group deleted between the two lookups suppresses nobody.
   *
   * @covers ::getRecipients
   */
  public function testLowerGroupDeletedMidFlightSuppressesNobody(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5], 2 => [5]];
    $this->missingGroups = [1];

    $this->assertSame([5], $this->notifiedUids(11));
  }

  /**
   * Media backed content sharing this context is never partitioned.
   *
   * The social_media_directory module builds its file, link and folder
   * notifications on this very activity context, directly for the "file",
   * "link" and "media_directory" templates and through subclasses for the
   * "_group", "_stream" and "file_version" ones. Their group relationships wrap
   * a media item or a taxonomy term, never a node, and cross-posting is a node
   * feature. Every member of every group must therefore keep their
   * notification, including the reader who belongs to several of those groups.
   *
   * The model is the one
   * testReaderOfTwoGroupsIsNotifiedByTheLowerOneOnly() partitions: reader 5
   * belongs to both groups. Swapping the node for a media item is the only
   * difference, so the two cases together isolate the guard.
   *
   * @covers ::getRecipients
   */
  public function testMediaBackedContentIsNeverPartitioned(): void {
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    $media = $this->createMock(MediaInterface::class);
    $media->method('getOwnerId')->willReturn(7);
    // The published state is only read for an entity the plugin recognised as
    // owned, so asserting it was read is the precondition that this case runs
    // the same branch a node runs and not an earlier return.
    $media->expects($this->atLeastOnce())->method('isPublished')->willReturn(TRUE);
    $media->method('access')->willReturn(TRUE);
    $this->relatedEntity = $media;

    $this->crossPostingService->expects($this->never())
      ->method('getGroupIdsByRelationshipForNodeWithoutAccessCheck');
    $this->messageStorage->expects($this->never())->method('getQuery');

    $this->assertEqualsCanonicalizing([5, 6], $this->notifiedUids(10));
    $this->assertEqualsCanonicalizing(
      [5, 8],
      $this->notifiedUids(11),
      'The reader of both groups keeps the notification of both.'
    );
  }

  /**
   * The challenge template is partitioned exactly like the group one.
   *
   * The social_challenge module owns the only other template of this context
   * whose relationships wrap a node, so it is the only other one the partition
   * can reach. Reaching it is intended: a reader of two challenges the same
   * idea was posted in would otherwise be notified twice.
   *
   * @covers ::getRecipients
   */
  public function testTheChallengeTemplateIsPartitionedTheSameWay(): void {
    $this->template = self::CHALLENGE_TEMPLATE;
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->templatePerRelationship = [
      10 => self::CHALLENGE_TEMPLATE,
      11 => self::CHALLENGE_TEMPLATE,
    ];
    $this->groupMembers = [1 => [5, 6], 2 => [5, 8]];

    $this->assertEqualsCanonicalizing(
      [5, 6],
      $this->notifiedUids(10),
      'The lowest challenge notifies every member of it.'
    );
    $this->assertEqualsCanonicalizing(
      [8],
      $this->notifiedUids(11),
      'The reader of both challenges is left to the lower one.'
    );
    $this->assertNotSame(
      [],
      $this->membershipQueries,
      'The partition really ran; the case is not green because it was skipped.'
    );
  }

  /**
   * A message of another template never suppresses a reader.
   *
   * A node can be a relationship of a challenge and of a flexible group at
   * once, and the two carry messages of different templates whose texts name
   * different groups. Counting one template's message towards the other's
   * ordering would hand the reader to a notification that speaks about a group
   * the other template never mentions.
   *
   * @covers ::getRecipients
   */
  public function testMessageOfAnotherTemplateSuppressesNobody(): void {
    // Relationship 10 is the challenge, relationship 11 the flexible group.
    $this->relationships = [10 => 1, 11 => 2];
    $this->relationshipsWithMessage = [10, 11];
    $this->templatePerRelationship = [10 => self::CHALLENGE_TEMPLATE];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $this->assertSame(
      [5],
      $this->notifiedUids(11),
      'The joined-group notification survives the challenge message.'
    );
    $this->assertSame(
      [],
      $this->membershipQueries,
      'A template with a single notifying relationship needs no lookup.'
    );

    $this->template = self::CHALLENGE_TEMPLATE;

    $this->assertSame(
      [5],
      $this->notifiedUids(10),
      'The challenge notification survives the joined-group message.'
    );
  }

  /**
   * Directly created activities notify a reader twice rather than never.
   *
   * The social_challenge template sets activity_create_direct, so every
   * activity is built while the messages of the relationships saved after it do
   * not exist yet.
   * The ordering then follows the order the messages were written in. This is
   * the order that loses the deduplication: the higher group is written first
   * and notifies, because at that moment it is the only relationship of the
   * template that carries a message at all.
   *
   * @covers ::getRecipients
   */
  public function testDirectlyCreatedActivitiesDuplicateRatherThanSilence(): void {
    $this->template = self::CHALLENGE_TEMPLATE;
    $this->relationships = [10 => 1, 11 => 2];
    $this->templatePerRelationship = [
      10 => self::CHALLENGE_TEMPLATE,
      11 => self::CHALLENGE_TEMPLATE,
    ];
    $this->groupMembers = [1 => [5], 2 => [5]];

    // The higher group is saved first and its activity built immediately.
    $this->relationshipsWithMessage = [11];

    $this->assertSame(
      [5],
      $this->notifiedUids(11),
      'The only relationship that notifies yet notifies the reader.'
    );

    // The lower group follows. It leads the ordering, so it notifies too.
    $this->relationshipsWithMessage = [11, 10];

    $this->assertSame(
      [5],
      $this->notifiedUids(10),
      'The lower group notifies as well, so the reader sees a duplicate.'
    );
  }

  /**
   * Directly created activities in the other order notify a reader once.
   *
   * The counterpart of
   * testDirectlyCreatedActivitiesDuplicateRatherThanSilence(): when the lower
   * group happens to be written first the partition holds, which is what makes
   * the duplicate of the other order a matter of luck rather than of design.
   *
   * @covers ::getRecipients
   */
  public function testDirectlyCreatedActivitiesDeduplicateInTheLowerFirstOrder(): void {
    $this->template = self::CHALLENGE_TEMPLATE;
    $this->relationships = [10 => 1, 11 => 2];
    $this->templatePerRelationship = [
      10 => self::CHALLENGE_TEMPLATE,
      11 => self::CHALLENGE_TEMPLATE,
    ];
    $this->groupMembers = [1 => [5], 2 => [5]];

    $this->relationshipsWithMessage = [10];

    $this->assertSame([5], $this->notifiedUids(10), 'The lower group notifies.');

    $this->relationshipsWithMessage = [10, 11];

    $this->assertSame(
      [],
      $this->notifiedUids(11),
      'The higher group leaves the reader to the lower one.'
    );
  }

}
