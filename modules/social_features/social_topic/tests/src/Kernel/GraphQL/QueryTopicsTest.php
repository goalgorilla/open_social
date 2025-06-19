<?php

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\NodeInterface;
use Drupal\social_topic\Plugin\GraphQL\DataProducer\TopicsCreated;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests the topics field on the Query type.
 *
 * @group social_graphql
 * @group social_topic
 */
class QueryTopicsTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'social_topic',
    'entity',
    // For the topic author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures topics for nodes.
    'node',
    // The default topic config contains a body text field.
    'field',
    'text',
    'filter',
    'file',
    'image',
    // For the comment functionality.
    'social_comment',
    'comment',
    // node.type.topic has a configuration dependency on the menu_ui module.
    'menu_ui',
    'entity_access_by_field',
    'options',
    'taxonomy',
    'path',
    'image_widget_crop',
    'crop',
    'field_group',
    'social_node',
    'social_core',
    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',
    'group',
    'variationcache',
    'path_alias',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('path_alias');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_topic',
      'filter',
    ]);
  }

  /**
   * Test that platform topics can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);

    $topics = [];

    for ($i = 0; $i < 10; ++$i) {
      $topics[] = $this->createNode([
        'type' => 'topic',
        'field_content_visibility' => 'public',
        'status' => NodeInterface::PUBLISHED,
      ]);
    }

    $topic_uuids = array_map(
      static fn($topic) => $topic->uuid(),
      $topics
    );

    $this->assertEndpointSupportsPagination(
      'topics',
      $topic_uuids
    );
  }

  /**
   * Test that a anonymous user can only see public topics.
   */
  public function testAnonymousUserCanViewOnlyPublicTopics() {
    $public_topic = $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'community',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'group',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);

    $this->assertResults('
          query {
            topics(last: 3) {
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
              nodes {
                id
              }
            }
          }
        ',
      [],
      [
        'topics' => [
          'pageInfo' => [
            'hasNextPage' => FALSE,
            'hasPreviousPage' => FALSE,
          ],
          'nodes' => [
            ['id' => $public_topic->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($public_topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a anonymous user can not see unpublished topics.
   */
  public function testAnonymousUserCanNotViewUnpublishedTopics() {
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $published_topic = $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);

    $this->assertResults('
          query {
            topics(last: 3) {
              nodes {
                id
              }
            }
          }
        ',
      [],
      [
        'topics' => [
          'nodes' => [
            ['id' => $published_topic->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($published_topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a user without permission can not see any topics.
   */
  public function testAnonymousUserCanNotViewTopicsWithoutPermission() {
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser();

    $this->assertResults('
          query {
            topics(last: 2) {
              nodes {
                id
              }
            }
          }
        ',
      [],
      [
        'topics' => [
          'nodes' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Helper method to get cache for topicsCreated tets.
   */
  private function createMetadataForTopicsCreated(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for topicsCreated tests.
   */
  private function getQueryForTopicsCreated(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          topicsCreated
        }
      }
    ';
  }

  /**
   * Test that the default value for the topicsCreated count is zero.
   */
  public function testUserCreatedTopicsIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'topicsCreated' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForTopicsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForTopicsCreated($user)
    );
  }

  /**
   * Test that adding a topic will increase the user's statistic count.
   */
  public function testUserCreatedTopicsCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create node.
    $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'uid' => $user->id(),
    ]);

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'topicsCreated' => 1,
      ],
    ];

    // Scenario: Adding a topic will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForTopicsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForTopicsCreated($user)
    );

  }

  /**
   * Test that deleting a topic is reflected in the number of topics created.
   */
  public function testUserCreatedTopicsDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create node.
    $node = $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'uid' => $user->id(),
    ]);

    // Delete node.
    $node->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'topicsCreated' => 0,
      ],
    ];

    // Scenario: Deleting a topic is reflected in the number of topics created
    // by the user.
    $this->assertResults(
      $this->getQueryForTopicsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForTopicsCreated($user)
    );

  }

  /**
   * Test that the database not called if cache is set.
   */
  public function testUserCreatedTopicsCached(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    // Set custom cache result.
    $new_result = 35;
    $cid = TopicsCreated::CID_BASE . $user->id();
    \Drupal::service('cache.default')
      ->set($cid, $new_result, Cache::PERMANENT, [$cid]);

    // Update expected counter.
    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'topicsCreated' => $new_result,
      ],
    ];

    // Scenario: Requesting the same statistic twice should not trigger
    // multiple database queries, the database not called if cache is set.
    $this->assertResults(
      $this->getQueryForTopicsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForTopicsCreated($user)
    );

  }

  /**
   * Test that the topicsCreated count is updated on owner change.
   */
  public function testUserCreatedTopicsAuthorChange(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Creating one node for current user.
    // Create node.
    $node = $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'uid' => $user->id(),
    ]);

    // Change owner to anonymous, for example.
    $node->setOwnerId(0);
    $node->save();

    // Making a call and our user should have 0.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'topicsCreated' => 0,
      ],
    ];
    $this->assertResults(
      $this->getQueryForTopicsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForTopicsCreated($user)
    );
  }

}
