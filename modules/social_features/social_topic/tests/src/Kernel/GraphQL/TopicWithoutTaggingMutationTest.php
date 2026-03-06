<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the topic mutations without tagging.
 *
 * @group social_topic
 */
class TopicWithoutTaggingMutationTest extends SocialGraphQLTestBase {

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
    'social_event_type',
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
      'social_editor',
      'social_core',
      'social_event',
      'social_event_type',
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
      'flag',
      'simple_oauth',
      'simple_oauth_static_scope',
    ]);

    // Configure OAuth to use static scope provider and set up keys.
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();
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
   * Test that the content tagging field is not secretly present.
   */
  public function testCreateTopicWithTaggingErrors(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->assertErrors(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
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
        'input' => [
          'clientMutationId' => $clientMutationId,
          'type' => $topicType->uuid(),
          'title' => 'Test Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","type":"' . $topicType->uuid() . '","title":"Test Topic","visibility":"PUBLIC","body":{"root":{"type":"root","version":1,"children":[{"type":"paragraph","version":1,"children":[{"type":"text","version":1,"text":"Hello"}]}]}},"contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type CreateTopicInput.',
      ],
      $this->defaultMutationCacheMetaData()
        // @todo Remove max age once https://www.drupal.org/project/simple_oauth/issues/3573262 is fixed.
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test updating a topic with all fields (without tagging).
   */
  public function testUpdateTopicSuccess(): void {
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

    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $updated_topic = Node::load($topic_id);
    assert($updated_topic !== NULL);
    $this->assertEquals('Updated Title', $updated_topic->getTitle());
    $this->assertEquals('public', $updated_topic->get('field_content_visibility')->value);
  }

  /**
   * Test that updateTopic rejects contentTags when tagging is not enabled.
   */
  public function testUpdateTopicWithTaggingErrors(): void {
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

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->assertErrors(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
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
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $topic->uuid(),
          'title' => 'Updated Title',
          'visibility' => 'PUBLIC',
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","id":"' . $topic->uuid() . '","title":"Updated Title","visibility":"PUBLIC","contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type UpdateTopicInput.',
      ],
      $this->defaultMutationCacheMetaData()
        // @todo Remove max age once https://www.drupal.org/project/simple_oauth/issues/3573262 is fixed.
        ->setCacheMaxAge(0)
    );
  }

}
