<?php

namespace Drupal\Tests\social_post\Kernel\GraphQL;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\social_post\Entity\Post;
use Drupal\social_post\Plugin\GraphQL\DataProducer\UserPostsCreated;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;

/**
 * Tests users endpoint to have postsCreated variable.
 *
 * @group social_graphql
 */
class PostsCountTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'file',
    'graphql_oauth',
    'social_graphql',
    'social_user',
    'role_delegation',
    'social_post',
    'path_alias',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installEntitySchema('post');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');

  }

  /**
   * Helper method to get cache for postsCreated tets.
   */
  private function createMetadataForpostsCreated(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for postsCreated tests.
   */
  private function getQueryForPostsCreated(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          postsCreated
        }
      }
    ';
  }

  /**
   * Test that the default value for the postsCreated count is zero.
   */
  public function testUserCreatedPostsIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'postsCreated' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForpostsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForpostsCreated($user)
    );
  }

  /**
   * Test that adding an event will increase the user's statistic count.
   */
  public function testUserCreatedPostsCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create post.
    Post::create([
      'type' => 'post',
      'user_id' => $user->id(),
    ])->save();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'postsCreated' => 1,
      ],
    ];

    // Scenario: Adding an event will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForpostsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForpostsCreated($user)
    );

  }

  /**
   * Test that deleting an event is reflected in the number of Posts created.
   */
  public function testUserCreatedPostsDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create post.
    $post = Post::create([
      'type' => 'post',
      'user_id' => $user->id(),
    ]);
    $post->save();

    // Delete post.
    $post->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'postsCreated' => 0,
      ],
    ];

    // Scenario: Deleting an event is reflected in the number of Posts created
    // by the user.
    $this->assertResults(
      $this->getQueryForpostsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForpostsCreated($user)
    );

  }

  /**
   * Test that the database not called if cache is set .
   */
  public function testUserCreatedPostsCached(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    // Set custom cache result.
    $new_result = 35;
    $cid = UserPostsCreated::CID_BASE . $user->id();
    \Drupal::service('cache.default')
      ->set($cid, $new_result, Cache::PERMANENT, [$cid]);

    // Update expected counter.
    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'postsCreated' => $new_result,
      ],
    ];

    // Scenario: Requesting the same statistic twice should not trigger
    // multiple database queries, the database not called if cache is set.
    $this->assertResults(
      $this->getQueryForpostsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForpostsCreated($user)
    );

  }

  /**
   * Test that the postsCreated count is updated on owner change.
   */
  public function testUserCreatedPostsAuthorChange(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Creating one post for current user.
    // Create post.
    $post = Post::create([
      'type' => 'post',
      'user_id' => $user->id(),
    ]);
    $post->save();

    // Change owner to anonymous, for example.
    $post->setOwnerId(0);
    $post->save();

    // Making a call and our user should have 0.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'postsCreated' => 0,
      ],
    ];
    $this->assertResults(
      $this->getQueryForpostsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForpostsCreated($user)
    );
  }

}
