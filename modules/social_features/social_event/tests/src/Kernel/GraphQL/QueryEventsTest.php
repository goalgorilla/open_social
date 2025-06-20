<?php

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\NodeInterface;
use Drupal\social_event\Plugin\GraphQL\DataProducer\EventsCreated;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests the events field on the Query type.
 *
 * @group social_graphql
 * @group social_event
 */
class QueryEventsTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'social_event',
    'entity',
    // For the event author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures events for nodes.
    'node',
    // The default event config contains a body text field.
    'field',
    'text',
    'filter',
    'file',
    'image',
    // For the comment functionality.
    'social_comment',
    'comment',
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
    'datetime',
    'address',
    'profile',
    'social_profile',
    'variationcache',
    'path_alias',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('comment');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'filter',
      'comment',
    ]);
  }

  /**
   * Test that platform events can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->setUpCurrentUser([], ['view node.event.field_content_visibility:public content']);

    $events = [];

    for ($i = 0; $i < 10; ++$i) {
      $events[] = $this->createNode([
        'type' => 'event',
        'field_content_visibility' => 'public',
        'status' => NodeInterface::PUBLISHED,
      ]);
    }

    $event_uuids = array_map(
      static fn($event) => $event->uuid(),
      $events
    );

    $this->assertEndpointSupportsPagination(
      'events',
      $event_uuids
    );
  }

  /**
   * Test that a anonymous user can only see public events.
   */
  public function testAnonymousUserCanViewOnlyPublicEvents() {
    $public_event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'community',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'group',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], ['view node.event.field_content_visibility:public content']);

    $this->assertResults('
          query {
            events(last: 3) {
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
        'events' => [
          'pageInfo' => [
            'hasNextPage' => FALSE,
            'hasPreviousPage' => FALSE,
          ],
          'nodes' => [
            ['id' => $public_event->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($public_event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a anonymous user can not see unpublished events.
   */
  public function testAnonymousUserCanNotViewUnpublishedEvents() {
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $published_event = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser([], ['view node.event.field_content_visibility:public content']);

    $this->assertResults('
          query {
            events(last: 3) {
              nodes {
                id
              }
            }
          }
        ',
      [],
      [
        'events' => [
          'nodes' => [
            ['id' => $published_event->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($published_event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a user without permission can not see any events.
   */
  public function testAnonymousUserCanNotViewEventsWithoutPermission() {
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->setUpCurrentUser();

    $this->assertResults('
          query {
            events(last: 2) {
              nodes {
                id
              }
            }
          }
        ',
      [],
      [
        'events' => [
          'nodes' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
    );
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
          eventsCreated
        }
      }
    ';
  }

  /**
   * Test that the default value for the eventsCreated count is zero.
   */
  public function testUserCreatedEventsIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'eventsCreated' => 0,
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
   * Test that adding an event will increase the user's statistic count.
   */
  public function testUserCreatedEventsCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create node.
    $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'uid' => $user->id(),
    ]);

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'eventsCreated' => 1,
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
   * Test that deleting an event is reflected in the number of events created.
   */
  public function testUserCreatedEventsDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Create node.
    $node = $this->createNode([
      'type' => 'event',
      'field_content_visibility' => 'public',
      'uid' => $user->id(),
    ]);

    // Delete node.
    $node->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'eventsCreated' => 0,
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
  public function testUserCreatedEventsCached(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));
    // Set custom cache result.
    $new_result = 35;
    $cid = EventsCreated::CID_BASE . $user->id();
    \Drupal::service('cache.default')
      ->set($cid, $new_result, Cache::PERMANENT, [$cid]);

    // Update expected counter.
    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'eventsCreated' => $new_result,
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

  /**
   * Test that the eventsCreated count is updated on owner change.
   */
  public function testUserCreatedEventsAuthorChange(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Creating one node for current user.
    // Create node.
    $node = $this->createNode([
      'type' => 'event',
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
        'eventsCreated' => 0,
      ],
    ];
    $this->assertResults(
      $this->getQueryForEventsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForEventsCreated($user)
    );
  }

}
