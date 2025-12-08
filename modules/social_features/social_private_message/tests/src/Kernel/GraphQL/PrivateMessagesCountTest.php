<?php

namespace Drupal\Tests\social_private_message\Kernel\GraphQL;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\user\UserInterface;

/**
 * Tests users endpoint to have privateMessageSent variable.
 *
 * @group social_graphql
 */
class PrivateMessagesCountTest extends SocialGraphQLTestBase {

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
    'private_message',
    'social_private_message',
    'text',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('private_message');
  }

  /**
   * Helper method to get cache for privateMessageSent tets.
   */
  private function createMetadataForPrivateMessageSent(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for privateMessageSent tests.
   */
  private function getQueryForPrivateMessageSent(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          privateMessageSent
        }
      }
    ';
  }

  /**
   * Test that the default value for the privateMessageSent count is zero.
   */
  public function testUserPrivateMessageSentIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'privateMessageSent' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForprivateMessageSent(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForprivateMessageSent($user)
    );
  }

  /**
   * Test that adding an event will increase the user's statistic count.
   */
  public function testUserPrivateMessageSentCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create item.
    PrivateMessage::create([
      'owner' => $user->id(),
    ])->save();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'privateMessageSent' => 1,
      ],
    ];

    // Scenario: Adding an event will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForprivateMessageSent(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForprivateMessageSent($user)
    );

  }

  /**
   * Test that deleting an event is reflected in the number of Posts created.
   */
  public function testUserPrivateMessageSentDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create item.
    $item = PrivateMessage::create([
      'owner' => $user->id(),
    ]);
    $item->save();

    // Delete item.
    $item->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'privateMessageSent' => 0,
      ],
    ];

    // Scenario: Deleting an event is reflected in the number of items created
    // by the user.
    $this->assertResults(
      $this->getQueryForprivateMessageSent(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForprivateMessageSent($user)
    );

  }

}
