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
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
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
      'social_editor',
      'social_event',
      'social_topic',
      'social_tagging',
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
   * Minimal valid Rich Text JSON body (paragraph with text).
   */
  private static function minimalRichTextBody(): array {
    return [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ];
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
   * Test updating a topic's body with Rich Text JSON.
   */
  public function testUpdateTopicWithBody(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => 'Original body', 'format' => 'basic_html']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$body: RichTextJSON) {
          updateTopic(input: {
            id: \$id
            body: \$body
          }) {
            errors
            topic {
              id
              bodyHtml
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'body' => self::minimalRichTextBody(),
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . $topic->id(), 'config:filter.format.basic_html'])
        ->addCacheContexts(['languages:language_interface'])
        ->setCacheMaxAge(0)
    );
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

  /**
   * Test updating a topic with content tags.
   */
  public function testUpdateTopicWithContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Innovation',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag2->save();

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
        mutation UpdateTopic(\$id: ID!, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            contentTags: \$contentTags
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
        'contentTags' => [$tag1->uuid(), $tag2->uuid()],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the content tags were actually updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($tag1->id(), $tag_ids, 'Tag 1 should be assigned to topic');
    $this->assertContains($tag2->id(), $tag_ids, 'Tag 2 should be assigned to topic');
  }

  /**
   * Test validation error for invalid content tag UUID (empty or non-string).
   */
  public function testUpdateTopicWithEmptyContentTagUuid(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

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

    $emptyUuid = '';

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
          'contentTags' => [$emptyUuid],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_NOT_FOUND:'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid content tag UUID (not found).
   */
  public function testUpdateTopicWithInvalidContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

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
          'contentTags' => [$fakeUuid],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ["CONTENT_TAG_NOT_FOUND:$fakeUuid"],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating topic with partial update including content tags.
   */
  public function testUpdateTopicPartialUpdateWithContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();
    $original_visibility = $topic->get('field_content_visibility')->value;

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$title: String, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            title: \$title
            contentTags: \$contentTags
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
        'title' => 'Updated Title with Tags',
        'contentTags' => [$tag1->uuid()],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title with Tags',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify both title and content tags were updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals('Updated Title with Tags', $updated_topic->getTitle());
    $this->assertEquals($original_visibility, $updated_topic->get('field_content_visibility')->value, 'Visibility should remain unchanged');
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($tag1->id(), $tag_ids, 'Tag should be assigned to topic');
  }

  /**
   * Test removing content tags by passing empty array.
   */
  public function testUpdateTopicRemoveContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Discussion',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Education',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'social_tagging' => [$tag1->id()],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            contentTags: \$contentTags
          }) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'contentTags' => [],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify content tags were removed.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values_after = $updated_topic->get('social_tagging')->getValue();
    $this->assertEmpty($tag_values_after, 'Content tags should be removed');
  }

  /**
   * Test validation error when providing too many content tags.
   */
  public function testUpdateTopicTooManyContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

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

    $fake_tag_uuids = [];
    for ($i = 0; $i < 200; $i++) {
      $fake_tag_uuids[] = sprintf('12345678-1234-1234-1234-%012d', $i);
    }

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
          'contentTags' => $fake_tag_uuids,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAGS_LIMIT_EXCEEDED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for content tag with invalid usage (not for topics).
   */
  public function testUpdateTopicWithInvalidContentTagUsage(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $tagA = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Tag',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tagA->save();

    $tagB = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Tag',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tagB->save();

    $tagC = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Shared Tag',
      'field_category_usage' => serialize(['node_event', 'node_topic']),
      'status' => 1,
    ]);
    $tagC->save();

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
          'contentTags' => [$tagA->uuid(), $tagB->uuid(), $tagC->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $tagA->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$tagB->uuid(), $tagC->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test topic with child tag that inherits field_category_usage from parent.
   */
  public function testUpdateTopicWithChildTagInheritingFromParent(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $parentTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Parent Category',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $parentTag->save();

    $childTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Child Tag',
      'parent' => [$parentTag->id()],
      'status' => 1,
    ]);
    $childTag->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($childTag->id(), $tag_ids, 'Child tag should be assigned to topic');
  }

  /**
   * Test tag without category_usage is rejected when parent don't allow topic.
   */
  public function testUpdateTopicWithChildTagRejectedWhenParentDoesNotAllowTopic(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $parentTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Parent Category',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $parentTag->save();

    $childTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Child Tag',
      'parent' => [$parentTag->id()],
      'status' => 1,
    ]);
    $childTag->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

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
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $childTag->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
