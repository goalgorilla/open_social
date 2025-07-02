<?php

namespace Drupal\Tests\social_like\Kernel\GraphQL;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\social_like\Plugin\GraphQL\DataProducer\UserLikesCreated;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;
use Drupal\votingapi\Entity\Vote;

/**
 * Tests users endpoint to have likes variable.
 *
 * @group social_graphql
 */
class LikesCountTest extends SocialGraphQLTestBase {

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
    'votingapi',
    'social_like',
    'path_alias',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('vote');
    $this->installEntitySchema('vote_result');
  }

  /**
   * Helper method to get cache for likes tets.
   */
  private function createMetadataForLikes(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for likes tests.
   */
  private function getQueryForLikes(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          likes
        }
      }
    ';
  }

  /**
   * Test that the default value for the likes count is zero.
   */
  public function testUserCreatedLikesIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'likes' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForLikes(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForLikes($user)
    );
  }

  /**
   * Test that adding a like will increase the user's statistic count.
   */
  public function testUserCreatedLikesCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    $like_user = $this->createUser();
    assert($like_user instanceof UserInterface);

    // Create like.
    Vote::create([
      'type' => 'like',
      'entity_id' => $like_user->id(),
      'entity_type' => 'user',
      'user_id' => $user->id(),
    ])->save();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'likes' => 1,
      ],
    ];

    // Scenario: Adding a like will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForLikes(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForLikes($user)
    );

  }

  /**
   * Test that deleting a like is reflected in the number of Likes created.
   */
  public function testUserCreatedLikesDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    $like_user = $this->createUser();
    assert($like_user instanceof UserInterface);

    // Create like.
    $like = Vote::create([
      'type' => 'like',
      'entity_id' => $like_user->id(),
      'entity_type' => 'user',
      'user_id' => $user->id(),
    ]);
    $like->save();

    // Delete like.
    $like->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'likes' => 0,
      ],
    ];

    // Scenario: Deleting a like is reflected in the number of Likes created
    // by the user.
    $this->assertResults(
      $this->getQueryForLikes(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForLikes($user)
    );

  }

  /**
   * Test that the database not called if cache is set.
   */
  public function testUserCreatedLikesCached(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    // Set custom cache result.
    $new_result = 35;
    $cid = UserLikesCreated::CID_BASE . $user->id();
    \Drupal::service('cache.default')
      ->set($cid, $new_result, Cache::PERMANENT, [$cid]);

    // Update expected counter.
    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'likes' => $new_result,
      ],
    ];

    // Scenario: Requesting the same statistic twice should not trigger
    // multiple database queries, the database not called if cache is set.
    $this->assertResults(
      $this->getQueryForLikes(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForLikes($user)
    );

  }

}
