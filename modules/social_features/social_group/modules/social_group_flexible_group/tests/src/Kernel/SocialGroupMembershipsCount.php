<?php

namespace Drupal\social_group_flexible_group\tests\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\Group;
use Drupal\social_group_flexible_group\Plugin\GraphQL\DataProducer\UserFlexibleGroupMemberships;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests the Group revision view revision page.
 */
class SocialGroupMembershipsCount extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

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
    // For field_group_allowed_join_method.
    'social_group',
    // Required for social_group_request.
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    // Required for requests.
    'social_group_flexible_group',
    'social_group_request',
    // Needed for field_media_file as field storage is defined by
    // "social_media_system".
    'social_media_system',
    // Required for select2 form display widget.
    'select2',
    // Needed for taxonomy as it uses "text_long" field type.
    'text',
    'pathauto',
    'smart_trim',
    // Required by pathauto.
    'path',
    'path_alias',
    'token',
    'inline_entity_form',
    'workflows',
    'content_moderation',
    'better_exposed_filters',
    'filter',
    'views_bulk_operations',
    'gnode',
    'social_event',
    'social_topic',
    'profile',
    'social_profile',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'social_node',
    'social_core',
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
    'file',
    'image',
    'options',
    'social_user',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'social_graphql',
    'graphql_oauth',
    'social_comment',
    'taxonomy',
    'role_delegation',
    'variationcache',
    'filter',
    'menu_link_content',
    'flag',
    'field',
    'social_group_invite',
    'ginvite',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('activity');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('pathauto_pattern');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('flagging');

    $this->installConfig([
      'node',
      'user',
      'profile',
      'menu_link_content',
      'social_profile',
      'social_node',
      'social_core',
      'group',
      'grequest',
      'social_event',
      'social_topic',
      'social_group_invite',
      'ginvite',
      'pathauto',
      'social_group',
      'social_group_flexible_group',
      'activity_creator',
    ]);
  }

  /**
   * Helper method to get cache for eventsCreated tets.
   */
  private function createMetadataForEventsCreated(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for eventsCreated tests.
   */
  private function getQueryForEventsCreated(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          flexibleGroupMembership
        }
      }
    ';
  }

  /**
   * Test that the default value for the flexibleGroupMembership count is zero.
   */
  public function testMembershipsCountIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'flexibleGroupMembership' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForEventsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForEventsCreated($user)
    );
  }

  /**
   * Test that adding a Membership will increase the user's statistic count.
   */
  public function testUserMembershipCreatedCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create group.
    $group = Group::create([
      'label' => $this->randomMachineName(),
      'type' => 'flexible_group',
      'uid' => 0,
      'status' => TRUE,
    ]);
    $group->save();

    // Create membership.
    $group->addMember($user);

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'flexibleGroupMembership' => 1,
      ],
    ];

    // Scenario: Adding an event will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForEventsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForEventsCreated($user)
    );

  }

  /**
   * Test that deleting a member is reflected in the number of items created.
   */
  public function testUserMembershipCreatedDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create group.
    $group = Group::create([
      'label' => $this->randomMachineName(),
      'type' => 'flexible_group',
      'uid' => 0,
      'status' => TRUE,
    ]);
    $group->save();

    // Create membership.
    $group->addMember($user);

    // Delete membership.
    $group->removeMember($user);

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'flexibleGroupMembership' => 0,
      ],
    ];

    // Scenario: Deleting an event is reflected in the number of events created
    // by the user.
    $this->assertResults(
      $this->getQueryForEventsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForEventsCreated($user)
    );

  }

  /**
   * Test that the database not called if cache is set .
   */
  public function testUserMembershipCached(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    // Set custom cache result.
    $new_result = 35;
    $cid = UserFlexibleGroupMemberships::CID_BASE . $user->id();
    \Drupal::service('cache.default')
      ->set($cid, $new_result, Cache::PERMANENT, [$cid]);

    // Update expected counter.
    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'flexibleGroupMembership' => $new_result,
      ],
    ];

    // Scenario: Requesting the same statistic twice should not trigger
    // multiple database queries, the database not called if cache is set.
    $this->assertResults(
      $this->getQueryForEventsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForEventsCreated($user)
    );

  }

}
