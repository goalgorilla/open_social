<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Core\Render\RenderContext;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for the createTopic GraphQL mutation.
 *
 * @group social_topic
 */
class CreateTopicMutationTest extends SocialGraphQLTestBase {

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
    // For field_group_allowed_join_method.
    'social_group',
    // Required for social_group_request.
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    // Required for requests.
    'social_group_flexible_group',
    'social_organization',
    'social_group_request',
    // Needed for field_media_file as field storage is defined by
    // "social_media_system".
    'social_media_system',
    // Required for select2 form display widget.
    'select2',
    // Needed for taxonomy as it uses "text_long" field type.
    'text',
    'pathauto',
    'smart_trim',
    // Required by pathauto.
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

    // Meeting API modules required by social_event configurations.
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
      'social_tagging',
      'node',
      'user',
      'profile',
      'menu_link_content',
      'social_profile',
      'social_node',
      'social_editor',
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

    // Set up UID 1 with all permissions.
    $this->setUpCurrentUser(
      ["uid" => 1],
      [
        'create topic content',
        'access content',
        'bypass node access',
        'administer nodes',
      ],
      // Don't check permissions.
      FALSE
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
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
   * Test creating a topic with all required fields.
   */
  public function testCreateTopicSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$type: ID!, \$title: String!, \$visibility: ContentVisibility!, \$body: RichTextJSON!, \$clientMutationId: UUIDv4) {
        createTopic(input: {
          clientMutationId: \$clientMutationId
          type: \$type
          title: \$title
          visibility: \$visibility
          body: \$body
        }) {
          clientMutationId
          errors
          topic {
            title
            type { id }
            visibility
            bodyHtml
          }
        }
      }
      GQL,
      [
        'clientMutationId' => $clientMutationId,
        'type' => $topicType->uuid(),
        'title' => 'Test Topic',
        'visibility' => 'PUBLIC',
        'body' => self::minimalRichTextBody(),
      ],
      [
        'createTopic' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'topic' => [
            'title' => 'Test Topic',
            'type' => [
              'id' => $topicType->uuid(),
            ],
            'visibility' => 'PUBLIC',
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1', 'taxonomy_term:1', 'config:filter.format.basic_html'])
        // @todo Remove max age once https://www.drupal.org/project/simple_oauth/issues/3573262 is fixed.
        ->setCacheMaxAge(0)
    );

  }

  /**
   * Test validation error when Rich Text JSON is invalid (missing root).
   */
  public function testCreateTopicInvalidRichTextJson(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Invalid body',
          'visibility' => 'PUBLIC',
          'body' => ['notRoot' => []],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['BODY_INVALID_STRUCTURE'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test validation error for missing required field (title).
   */
  public function testCreateTopicMissingTitle(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => '',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => [
            'TITLE_REQUIRED',
          ],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData(),
    );
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testCreateTopicTitleTooLong(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    // Create a title longer than 255 characters.
    $longTitle = str_repeat('A', 256);

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => $longTitle,
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => [
            'TITLE_TOO_LONG',
          ],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData(),
    );
  }

  /**
   * Test validation error for invalid topic type UUID.
   */
  public function testCreateTopicInvalidTopicType(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $fakeUuid,
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => [
            'TOPIC_TYPE_NOT_FOUND',
          ],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test creating topic with public visibility.
   */
  public function testCreateTopicPublicVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $visibility = 'PUBLIC';

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            visibility
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => "Topic with $visibility visibility",
          'visibility' => $visibility,
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => [
            'visibility' => $visibility,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1'])
    );
  }

  /**
   * Test creating topic with community visibility.
   */
  public function testCreateTopicCommunityVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $visibility = 'COMMUNITY';

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            visibility
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => "Topic with $visibility visibility",
          'visibility' => $visibility,
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => [
            'visibility' => $visibility,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1'])
    );
  }

  /**
   * Test that creating a topic requires the topic:write scope.
   */
  public function testCreateTopicRequiresTopicWriteScope(): void {
    // Act as client credentials without the required scope.
    $this->actAsClientCredentialsWithScopes([]);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $this->assertErrors(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        "Missing scope 'topic:write' on 'createTopic'.",
      ],
      $this->defaultMutationCacheMetaData(),
    );
  }

  /**
   * Test creating a topic with content tags.
   */
  public function testCreateTopicWithTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Tutorial',
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
      'name' => 'Education',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag2->save();

    $clientMutationId = '650e8400-e29b-41d4-a716-446655440001';

    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          clientMutationId
          errors
          topic {
            title
          }
        }
      }
      GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'type' => $topicType->uuid(),
          'title' => 'Tutorial: Getting Started',
          'visibility' => 'COMMUNITY',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$tag1->uuid(), $tag2->uuid()],
        ],
      ],
      [
        'createTopic' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'topic' => [
            'title' => 'Tutorial: Getting Started',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $created_topic = $node_storage->load(1);
    assert($created_topic !== NULL);

    $this->assertEquals(
      [
        ['target_id' => $tag1->id()],
        ['target_id' => $tag2->id()],
      ],
      $created_topic->get('social_tagging')->getValue(),
      "Expected the created topic to have the content tags associated in the correct order.",
    );
  }

  /**
   * Test validation error for invalid content tag UUID.
   */
  public function testCreateTopicInvalidContentTag(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $fakeTagUuid = '12345678-1234-1234-1234-123456789999';

    $this->assertResults(
      <<<GQL
        mutation CreateTopic(\$input: CreateTopicInput!) {
          createTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$fakeTagUuid],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CONTENT_TAG_NOT_FOUND:' . $fakeTagUuid],
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
  public function testCreateTopicWithInvalidContentTagUsage(): void {
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

    $this->assertResults(
      <<<GQL
        mutation CreateTopic(\$input: CreateTopicInput!) {
          createTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$tagA->uuid(), $tagB->uuid(), $tagC->uuid()],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $tagA->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $query = <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic {
            id
            title
          }
        }
      }
      GQL;

    $variables = [
      'input' => [
        'type' => $topicType->uuid(),
        'title' => 'Test Topic With Valid Tags',
        'visibility' => 'PUBLIC',
        'body' => self::minimalRichTextBody(),
        'contentTags' => [$tagB->uuid(), $tagC->uuid()],
      ],
    ];

    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($query, $variables) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => $query,
            'variables' => $variables,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors, 'No GraphQL errors expected');
    $data = $result->toArray();
    $this->assertArrayHasKey('data', $data, 'No result data.');
    $this->assertEmpty($data['data']['createTopic']['errors']);
    $this->assertNotNull($data['data']['createTopic']['topic']);
    $this->assertEquals('Test Topic With Valid Tags', $data['data']['createTopic']['topic']['title']);
    $topic_id = $data['data']['createTopic']['topic']['id'];
    $this->assertNotEmpty($topic_id, 'Topic ID should be returned');

    $entity_repository = \Drupal::service('entity.repository');
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $created_topic = $entity_repository->loadEntityByUuid('node', $topic_id);
    $this->assertNotNull($created_topic, 'Topic should be created');
    /** @var \Drupal\node\NodeInterface $created_topic */
    $tag_values = $created_topic->get('social_tagging')->getValue();
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($tagB->id(), $tag_ids, 'Tag B (topic-only) should be assigned to topic');
    $this->assertContains($tagC->id(), $tag_ids, 'Tag C (shared) should be assigned to topic');
    $this->assertNotContains($tagA->id(), $tag_ids, 'Tag A (event-only) should NOT be assigned to topic');
  }

  /**
   * Test validation error when providing too many content tags.
   */
  public function testCreateTopicTooManyContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $fake_tag_uuids = [];
    for ($i = 0; $i < 200; $i++) {
      $fake_tag_uuids[] = sprintf('12345678-1234-1234-1234-%012d', $i);
    }

    $this->assertResults(
      <<<GQL
        mutation CreateTopic(\$input: CreateTopicInput!) {
          createTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => $fake_tag_uuids,
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CONTENT_TAGS_LIMIT_EXCEEDED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test topic with child tag that inherits field_category_usage from parent.
   */
  public function testCreateTopicWithChildTagInheritingFromParent(): void {
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

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

    $this->assertResults(
      <<<GQL
        mutation CreateTopic(\$input: CreateTopicInput!) {
          createTopic(input: \$input) {
            errors
            topic {
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic with Child Tag',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => [
            'title' => 'Topic with Child Tag',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheTags(['node:1'])
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the child tag was actually saved to the topic.
    $entity_repository = \Drupal::service('entity.repository');
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadByProperties([
      'type' => 'topic',
      'title' => 'Topic with Child Tag',
    ]);
    $this->assertNotEmpty($nodes, 'Topic should be created');
    $created_topic = reset($nodes);
    /** @var \Drupal\node\NodeInterface $created_topic */
    $tag_values = $created_topic->get('social_tagging')->getValue();
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
  public function testCreateTopicWithChildTagRejectedWhenParentDoesNotAllowTopic(): void {
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

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

    $this->assertResults(
      <<<GQL
        mutation CreateTopic(\$input: CreateTopicInput!) {
          createTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $childTag->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
