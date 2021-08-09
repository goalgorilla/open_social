<?php

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

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
  public static $modules = [
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'filter',
    ]);
  }

  /**
   * Test that platform events can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->setUpCurrentUser([], ['view node.event.field_content_visibility:public content']);

    for ($i = 0; $i < 10; ++$i) {
      $events[] = $this->createNode([
        'type' => 'event',
        'field_content_visibility' => 'public',
        'status' => NodeInterface::PUBLISHED,
      ]);
    }

    $event_uuids = array_map(
      static fn($event) => $event->uuid(),
      $events ?? []
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

}
