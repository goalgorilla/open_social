<?php

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

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
  public static $modules = [
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
      'social_topic',
      'filter',
    ]);
  }

  /**
   * Test that platform topics can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->setUpCurrentUser([], ['view node.topic.field_content_visibility:public content']);

    for ($i = 0; $i < 10; ++$i) {
      $topics[] = $this->createNode([
        'type' => 'topic',
        'field_content_visibility' => 'public',
        'status' => NodeInterface::PUBLISHED,
      ]);
    }

    $topic_uuids = array_map(
      static fn($topic) => $topic->uuid(),
      $topics ?? []
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

}
