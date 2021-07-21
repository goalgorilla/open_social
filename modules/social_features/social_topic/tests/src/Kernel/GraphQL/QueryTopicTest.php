<?php

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the topic field on the Query type.
 *
 * @group social_graphql
 * @group social_topic
 */
class QueryTopicTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use TaxonomyTestTrait;

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
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_topic',
      'filter',
      'comment',
      'social_comment',
    ]);
  }

  /**
   * Test that the fields provided by the module can be queried.
   */
  public function testSupportsFieldsIncludedInModule() : void {
    $topic_types = Vocabulary::load('topic_types');
    $topic_type_term = $this->createTerm($topic_types);

    $raw_topic_body = "This is a link test: https://social.localhost";
    $html_topic_body = "<div><p>This is a link test: <a href=\"https://social.localhost\">https://social.localhost</a></p>\n</div>";

    $topic_image = File::create();
    $topic_image->setFileUri('core/misc/druplicon.png');
    $topic_image->setFilename(\Drupal::service('file_system')->basename($topic_image->getFileUri()));
    $topic_image->save();

    $topic = $this->createNode([
      'type' => 'topic',
      'field_topic_type' => $topic_type_term->id(),
      'field_content_visibility' => 'public',
      'field_topic_image' => $topic_image->id(),
      'status' => NodeInterface::PUBLISHED,
      'body' => $raw_topic_body,
    ]);

    // Set up a user that can view public topics and create a published
    // comment and view it.
    $this->setUpCurrentUser([], array_merge([
      'skip comment approval',
      'access comments',
      'view node.topic.field_content_visibility:public content',
    ], $this->userPermissions()));

    $comment = Comment::create([
      'entity_id' => $topic->id(),
      'entity_type' => $topic->getEntityTypeId(),
      'comment_type' => 'comment',
      'field_name' => 'comments',
    ]);
    $comment->save();

    $query = '
      query ($id: ID!) {
        topic(id: $id) {
          id
          title
          author {
            id
            displayName
          }
          type {
            id
            label
          }
          bodyHtml
          comments(first: 1) {
            nodes {
              id
            }
          }
          url
          created {
            timestamp
          }
          heroImage {
            url
          }
        }
      }
    ';

    $this->assertResults(
      $query,
      ['id' => $topic->uuid()],
      [
        'topic' => [
          'id' => $topic->uuid(),
          'title' => $topic->label(),
          'author' => [
            'id' => $topic->getOwner()->uuid(),
            'displayName' => $topic->getOwner()->getDisplayName(),
          ],
          'type' => [
            'id' => $topic_type_term->uuid(),
            'label' => $topic_type_term->label(),
          ],
          'bodyHtml' => $html_topic_body,
          'comments' => [
            'nodes' => [
              ['id' => $comment->uuid()],
            ],
          ],
          'url' => $topic->toUrl('canonical', ['absolute' => TRUE])->toString(),
          'created' => [
            'timestamp' => $topic->getCreatedTime(),
          ],
          'heroImage' => [
            'url' => file_create_url($topic_image->getFileUri()),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($topic)
        ->addCacheableDependency($topic->getOwner())
        ->addCacheTags(['taxonomy_term:1', 'config:filter.format.plain_text', 'config:filter.settings', 'comment:1'])
        ->addCacheContexts(['languages:language_interface', 'user.node_grants:view', 'url.site'])
    );
  }

  /**
   * Test that it respects the access topics permission.
   */
  public function testRequiresAccessTopicsPermission() {
    $topic = $this->createNode([
      'type' => 'topic',
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ]);

    // Create a user that is not allowed to view public topics and comments.
    $this->setUpCurrentUser();

    $this->assertResults('
        query ($id: ID!) {
          topic(id: $id) {
            id
          }
        }
      ',
      ['id' => $topic->uuid()],
      ['topic' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
