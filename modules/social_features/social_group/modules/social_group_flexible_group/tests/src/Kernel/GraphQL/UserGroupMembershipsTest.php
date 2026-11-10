<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Kernel\GraphQL;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\graphql_oauth\Entity\Server;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the User.groupMemberships GraphQL field.
 *
 * @group social_graphql
 */
class UserGroupMembershipsTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Member whose group memberships are queried.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testUser;

  /**
   * Viewer who is not a member of the members-only groups.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testViewer;

  /**
   * Public-visible group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $publicGroup;

  /**
   * Community-visible group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $communityGroup;

  /**
   * Members-only group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $membersGroup;

  /**
   * Unpublished group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $unpublishedGroup;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'datetime',
    'subgroup',
    'paragraphs',
    'image',
    'options',
    'file',
    'link',
    'entity_reference_revisions',
    'media',
    'node',
    'grequest',
    'state_machine',
    'social_group',
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    'social_group_flexible_group',
    'social_group_request',
    'social_media_system',
    'select2',
    'text',
    'taxonomy',
    'taxonomy_access_fix',
    'pathauto',
    'smart_trim',
    'path',
    'path_alias',
    'token',
    'diff',
    'inline_entity_form',
    'workflows',
    'content_moderation',
    'better_exposed_filters',
    'filter',
    'views_bulk_operations',
    'flag',
    'gnode',
    'social_event',
    'social_event_type',
    'social_topic',
    'social_tagging',
    'profile',
    'social_profile',
    'social_profile_privacy',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'social_node',
    'social_core',
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
    'field_group',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'block',
    'block_content',
    'entity_access_by_field',
    'entity',
    'entity_test',
    'telephone',
    'lazy',
    'serialization',
    'group',
    'social_user',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'social_graphql',
    'graphql_oauth',
    'social_comment',
    'role_delegation',
    'menu_link_content',
    'social_group_invite',
    'ginvite',
    'entity_browser',
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('file');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('pathauto_pattern');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('user');
    $this->installEntitySchema('flag');
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('activity');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('comment', ['comment_entity_statistics']);

    $this->installConfig([
      'node',
      'user',
      'profile',
      'menu_link_content',
      'social_profile',
      'social_profile_privacy',
      'social_node',
      'social_core',
      'group',
      'grequest',
      'state_machine',
      'social_event',
      'social_event_type',
      'social_topic',
      'social_tagging',
      'social_group_invite',
      'ginvite',
      'social_group',
      'activity_logger',
      'activity_creator',
      'social_group_flexible_group',
      'social_group_request',
      'social_media_system',
      'file',
      'field',
      'media',
      'taxonomy',
      'text',
      'paragraphs',
      'system',
      'pathauto',
      'smart_trim',
      'subgroup',
      'simple_oauth',
      'simple_oauth_static_scope',
      'flag',
      'social_editor',
    ]);

    $this->config('simple_oauth.settings')
      ->set('scope_provider', 'static')
      ->save();
    $this->setUpKeys();
    $this->assertInstanceOf(Server::class, $this->server);

    $this->setUpCurrentUser(['uid' => 1], [], TRUE);
  }

  /**
   * A member sees every group membership they belong to.
   *
   * Test:
   *   Published public, community, and members-only groups are listed.
   *   Unpublished groups are omitted.
   */
  public function testMemberSeesOwnGroupMemberships(): void {
    $this->createMembershipSetup();
    $this->setCurrentUser($this->testUser);

    $expected_data = [
      'user' => [
        'groupMemberships' => [
          'nodes' => $this->expectedMembershipNodes([
            $this->publicGroup,
            $this->communityGroup,
            $this->membersGroup,
          ]),
        ],
      ],
    ];

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $this->createMetadata([
        $this->publicGroup,
        $this->communityGroup,
        $this->membersGroup,
      ]),
    );
  }

  /**
   * A non-member does not see members-only group memberships of another user.
   *
   * Test:
   *   Public and community groups remain visible.
   *   Members-only and unpublished groups are omitted.
   */
  public function testViewerSeesOnlyAccessibleGroupMemberships(): void {
    $this->createMembershipSetup();
    $this->setCurrentUser($this->testViewer);

    $expected_data = [
      'user' => [
        'groupMemberships' => [
          'nodes' => $this->expectedMembershipNodes([
            $this->publicGroup,
            $this->communityGroup,
          ]),
        ],
      ],
    ];

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $this->createMetadata([
        $this->publicGroup,
        $this->communityGroup,
      ]),
    );
  }

  /**
   * A user with no group memberships gets an empty results.
   */
  public function testNoMembershipsReturnsEmptyResult(): void {
    $this->createTestUsers();
    $this->setCurrentUser($this->testUser);

    $expected_data = [
      'user' => [
        'groupMemberships' => [
          'nodes' => [],
        ],
      ],
    ];

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $this->createMetadata(),
    );
  }

  /**
   * Joining a group adds it to a later groupMemberships query.
   */
  public function testGroupMembershipsIncludesGroupAfterJoin(): void {
    $this->createTestUsers();
    $group = $this->createFlexibleGroup(
      'Joined group',
      'community',
      $this->createGroupOwner(),
    );
    $this->setCurrentUser($this->testUser);

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      [
        'user' => [
          'groupMemberships' => [
            'nodes' => [],
          ],
        ],
      ],
      $this->createMetadata(),
    );

    $group->addMember($this->testUser);

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      [
        'user' => [
          'groupMemberships' => [
            'nodes' => $this->expectedMembershipNodes([$group]),
          ],
        ],
      ],
      $this->createMetadata([$group]),
    );
  }

  /**
   * Leaving a group removes it from a later groupMemberships query.
   */
  public function testGroupMembershipsOmitsGroupAfterLeave(): void {
    $this->createTestUsers();
    $group = $this->createFlexibleGroup(
      'Left group',
      'community',
      $this->createGroupOwner(),
    );
    $group->addMember($this->testUser);
    $this->setCurrentUser($this->testUser);

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      [
        'user' => [
          'groupMemberships' => [
            'nodes' => $this->expectedMembershipNodes([$group]),
          ],
        ],
      ],
      $this->createMetadata([$group]),
    );

    $group->removeMember($this->testUser);

    $this->assertResults(
      $this->getQuery(),
      ['id' => $this->testUser->uuid()],
      [
        'user' => [
          'groupMemberships' => [
            'nodes' => [],
          ],
        ],
      ],
      $this->createMetadata(),
    );
  }

  /**
   * Creates GraphQL users.
   */
  private function createTestUsers(): void {
    $test_user = $this->createUser([
      'execute open_social_graphql arbitrary graphql requests',
      'access user profiles',
      'view any profile',
      'view public flexible_group group',
      'view community flexible_group group',
      'view members flexible_group group',
    ]);
    $this->assertNotFalse($test_user);
    $this->testUser = $test_user;

    $test_viewer = $this->createUser([
      'execute open_social_graphql arbitrary graphql requests',
      'access user profiles',
      'view any profile',
      'view public flexible_group group',
      'view community flexible_group group',
    ]);
    $this->assertNotFalse($test_viewer);
    $this->testViewer = $test_viewer;
  }

  /**
   * Creates a user that owns groups but is not the queried member.
   */
  private function createGroupOwner(): UserInterface {
    $group_owner = $this->createUser([]);
    $this->assertNotFalse($group_owner);
    return $group_owner;
  }

  /**
   * Creates a member of public, community, members, and unpublished groups.
   */
  private function createMembershipSetup(): void {
    $this->createTestUsers();
    $group_owner = $this->createGroupOwner();

    $this->publicGroup = $this->createFlexibleGroup(
      'Public group',
      'public',
      $group_owner,
    );
    $this->communityGroup = $this->createFlexibleGroup(
      'Community group',
      'community',
      $group_owner,
    );
    $this->membersGroup = $this->createFlexibleGroup(
      'Members only group',
      'members',
      $group_owner,
    );
    $this->unpublishedGroup = $this->createFlexibleGroup(
      'Unpublished group',
      'community',
      $group_owner,
      FALSE,
    );

    foreach ($this->allMembershipGroups() as $group) {
      $group->addMember($this->testUser);
    }
  }

  /**
   * Groups the queried user is a member of, in join order.
   *
   * @return list<\Drupal\group\Entity\GroupInterface>
   *   Membership groups.
   */
  private function allMembershipGroups(): array {
    return [
      $this->publicGroup,
      $this->communityGroup,
      $this->membersGroup,
      $this->unpublishedGroup,
    ];
  }

  /**
   * Creates a flexible group with the given visibility.
   */
  private function createFlexibleGroup(
    string $label,
    string $visibility,
    UserInterface $owner,
    bool $status = TRUE,
  ): Group {
    $group = Group::create([
      'label' => $label,
      'type' => 'flexible_group',
      'uid' => $owner->id(),
      'status' => $status,
      'field_flexible_group_visibility' => $visibility,
    ]);
    $group->save();
    return $group;
  }

  /**
   * Expected GraphQL membership nodes sorted by join date.
   *
   * @param list<\Drupal\group\Entity\GroupInterface> $groups
   *   Groups whose memberships should appear in the response.
   *
   * @return list<array{id: string, group: array{id: string, name: string}}>
   *   Membership nodes in JOINED_AT order.
   */
  private function expectedMembershipNodes(array $groups): array {
    $memberships = [];
    foreach ($groups as $group) {
      $membership = GroupMembership::loadSingle($group, $this->testUser);
      $this->assertInstanceOf(GroupMembership::class, $membership);
      $memberships[] = $membership;
    }

    usort(
      $memberships,
      static fn (GroupMembership $a, GroupMembership $b): int => $a->getCreatedTime() <=> $b->getCreatedTime(),
    );

    return array_map(
      fn (GroupMembership $membership): array => $this->expectedMembershipData($membership),
      $memberships,
    );
  }

  /**
   * Expected GraphQL membership payload.
   *
   * @return array{id: string, group: array{id: string, name: string}}
   *   Membership id and nested group data.
   */
  private function expectedMembershipData(GroupMembership $membership): array {
    $membership_uuid = $membership->uuid();
    $this->assertIsString($membership_uuid);

    $group = $membership->getGroup();
    $group_uuid = $group->uuid();
    $this->assertIsString($group_uuid);
    $name = $group->label();
    $this->assertIsString($name);

    return [
      'id' => $membership_uuid,
      'group' => [
        'id' => $group_uuid,
        'name' => $name,
      ],
    ];
  }

  /**
   * Cache metadata for User.groupMemberships.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $visible_groups
   *   Groups whose memberships appear in the response.
   */
  private function createMetadata(array $visible_groups = []): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->mergeCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
      'user',
      'user.group_permissions',
    ]);
    $cache_metadata->addCacheableDependency($this->testUser);
    $cache_metadata->addCacheTags(["group_content_list:plugin:group_membership:entity:{$this->testUser->id()}"]);

    foreach ($visible_groups as $group) {
      $cache_metadata->addCacheableDependency($group);
    }
    $this->addMembershipCacheability($cache_metadata, $visible_groups);

    return $cache_metadata;
  }

  /**
   * Adds visible group memberships as cache dependencies.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cache_metadata
   *   Cache metadata to extend.
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   Groups whose memberships appear in the response.
   */
  private function addMembershipCacheability(CacheableMetadata $cache_metadata, array $groups): void {
    foreach ($groups as $group) {
      $membership = GroupMembership::loadSingle($group, $this->testUser);
      if ($membership instanceof GroupMembership) {
        $cache_metadata->addCacheableDependency($membership);
      }
    }
  }

  /**
   * Query for groupMemberships id and nested group fields.
   */
  private function getQuery(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          groupMemberships(first: 50) {
            nodes {
              id
              group {
                id
                name
              }
            }
          }
        }
      }
    ';
  }

}
