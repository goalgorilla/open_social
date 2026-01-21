<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for the updateTopic GraphQL mutation.
 *
 * @group social_topic
 */
class UpdateTopicMutationTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

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
    'social_group',
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    'social_group_flexible_group',
    'social_organization',
    'social_group_request',
    'social_media_system',
    'select2',
    'text',
    'pathauto',
    'smart_trim',
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
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
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
    'menu_link_content',
    'flag',
    'field',
    'social_group_invite',
    'ginvite',
    'layout_builder',
    'social_tagging',
    'layout_discovery',
    'flag_count',
    'hux',
    'taxonomy_access_fix',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
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
    $this->installEntitySchema('flag');
    $this->installSchema('flag', ['flag_counts']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('crop');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('layout_builder', ['inline_block_usage']);

    $this->installConfig([
      'node',
      'user',
      'profile',
      'menu_link_content',
      'social_profile',
      'social_node',
      'social_core',
      'social_event',
      'social_topic',
      'social_group_invite',
      'ginvite',
      'pathauto',
      'social_group',
      'grequest',
      'group',
      'activity_creator',
      'activity_logger',
      'layout_builder',
      'layout_discovery',
      'social_group_flexible_group',
      'social_group_request',
      'social_organization',
      'flag',
      'simple_oauth',
      'simple_oauth_static_scope',
    ]);

    // Configure OAuth to use static scope provider and set up keys.
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();
  }

  /**
   * Test updating a topic with all fields.
   */
  public function testUpdateTopicSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$type: ID, \$title: String, \$visibility: ContentVisibility, \$clientMutationId: UUIDv4) {
          updateTopic(input: {
            clientMutationId: \$clientMutationId
            id: \$id
            type: \$type
            title: \$title
            visibility: \$visibility
          }) {
            clientMutationId
            errors
            topic {
              id
              title
              visibility
            }
          }
        }
        GQL,
      [
        'clientMutationId' => $clientMutationId,
        'id' => $topic->uuid(),
        'type' => $topicType->uuid(),
        'title' => 'Updated Title',
        'visibility' => 'PUBLIC',
      ],
      [
        'updateTopic' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title',
            'visibility' => 'PUBLIC',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the node was actually updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals('Updated Title', $updated_topic->getTitle());
    $this->assertEquals('public', $updated_topic->get('field_content_visibility')->value);
  }

  /**
   * Test updating a topic with only title (partial update).
   */
  public function testUpdateTopicPartialUpdate(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();
    $original_body = $topic->get('body')->value;
    $original_visibility = $topic->get('field_content_visibility')->value;

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$title: String) {
          updateTopic(input: {
            id: \$id
            title: \$title
          }) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'title' => 'Updated Title Only',
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title Only',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify other fields remain unchanged.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals($original_body, $updated_topic->get('body')->value);
    $this->assertEquals($original_visibility, $updated_topic->get('field_content_visibility')->value);
  }

  /**
   * Test validation error for missing topic ID.
   *
   * Note: Since id is required in the GraphQL schema (ID!), GraphQL will
   * return a schema validation error before the mutation executes.
   */
  public function testUpdateTopicMissingId(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Execute mutation without ID - Will return schema validation error.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation UpdateTopic(\$input: UpdateTopicInput!) {
                updateTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'title' => 'Test Title',
              ],
            ],
          ])
        );
      }
    );

    // GraphQL schema validation error because id is required.
    $this->assertNotEmpty($result->errors, 'Expected GraphQL schema validation error for missing required field id');
  }

  /**
   * Test validation error for invalid topic ID.
   */
  public function testUpdateTopicInvalidId(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $fakeUuid,
          'title' => 'Test Title',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TOPIC_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testUpdateTopicTitleTooLong(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Create a title longer than 255 characters.
    $longTitle = str_repeat('A', 256);

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => $longTitle,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TITLE_TOO_LONG'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for empty title.
   */
  public function testUpdateTopicEmptyTitle(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Discussion',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => '',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TITLE_REQUIRED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid topic type UUID.
   */
  public function testUpdateTopicInvalidTopicType(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'type' => $fakeUuid,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TOPIC_TYPE_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Data provider for visibility test cases.
   *
   * @return array
   *   Array of visibility values to test.
   */
  public function provideVisibilities(): array {
    return [
      'PUBLIC' => ['PUBLIC'],
      'COMMUNITY' => ['COMMUNITY'],
      'GROUP_MEMBER' => ['GROUP_MEMBER'],
    ];
  }

  /**
   * Test updating topic with different visibility levels.
   *
   * @param string $visibility
   *   The visibility level to test.
   *
   * @dataProvider provideVisibilities
   */
  public function testUpdateTopicDifferentVisibilities(string $visibility): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => "Topic with $visibility visibility",
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              visibility
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => $visibility,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'visibility' => $visibility,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that updating a topic requires the topic:write scope.
   */
  public function testUpdateTopicRequiresTopicWriteScope(): void {
    // Act as client credentials without the required scope.
    $this->actAsClientCredentialsWithScopes([]);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Execute mutation without the required scope.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation UpdateTopic(\$input: UpdateTopicInput!) {
                updateTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $topic->uuid(),
                'title' => 'Test Topic',
              ],
            ],
          ])
        );
      }
    );

    // Should have GraphQL errors about missing scope.
    $this->assertNotEmpty($result->errors);
    $errorMessages = array_map(
      fn($error) => is_object($error) && method_exists($error, 'getMessage')
        ? $error->getMessage()
        : (is_array($error) ? json_encode($error) : (string) $error),
      $result->errors
    );
    $errorMessage = implode(' | ', $errorMessages);
    $this->assertStringContainsString("Missing scope 'topic:write'", $errorMessage, 'Expected error about missing topic:write scope');

    // Verify the topic title remains unchanged.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $updated_topic = $node_storage->load($topic_id);
    assert($updated_topic instanceof NodeInterface);
    $this->assertEquals('Original Title', $updated_topic->getTitle(), 'Topic title should remain unchanged after scope failure');
  }

}
