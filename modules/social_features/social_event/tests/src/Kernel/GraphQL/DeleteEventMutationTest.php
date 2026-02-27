<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the deleteEvent GraphQL mutation.
 *
 * @group social_event
 */
class DeleteEventMutationTest extends SocialGraphQLTestBase {

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
    'social_tagging',
    'layout_discovery',
    'flag_count',
    'hux',
    // Required for taxonomy access permissions (view terms in event_types,
    // select terms in event_types).
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
      'social_organization',
      'flag',
      'simple_oauth',
      'simple_oauth_static_scope',
      'social_editor',
    ]);

    // Configure OAuth to use static scope provider and set up keys.
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    $this->setUpCurrentUser(["uid" => 1], [], FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
  }

  /**
   * Creates a minimal event node for delete tests.
   *
   * @param array $overrides
   *   Optional field overrides.
   *
   * @return \Drupal\node\NodeInterface
   *   The created event node.
   */
  protected function createEventNode(array $overrides = []): NodeInterface {
    $values = array_merge([
      'type' => 'event',
      'title' => 'Test Event to Delete',
      'field_content_visibility' => 'public',
      'field_event_date' => date('Y-m-d\TH:i:s', strtotime('+1 day')),
      'field_event_date_end' => date('Y-m-d\TH:i:s', strtotime('+2 days')),
      'uid' => 2,
      'status' => 1,
    ], $overrides);

    $node = Node::create($values);
    $node->save();
    return $node;
  }

  /**
   * Test deleting an event successfully.
   */
  public function testDeleteEventSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $event = $this->createEventNode();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $event_uuid = $event->uuid();
    assert(is_string($event_uuid));

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            clientMutationId
            errors
          }
        }
        GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $event_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the event was actually removed from storage.
    $entity_repository = $this->container->get('entity.repository');
    $loaded_node = $entity_repository->loadEntityByUuid('node', $event_uuid);
    $this->assertNull($loaded_node, 'Event should be deleted.');
  }

  /**
   * Test validation error for missing event ID.
   */
  public function testDeleteEventMissingId(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => '',
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_ID_REQUIRED'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for non-existent event.
   */
  public function testDeleteEventNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $fakeUuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_NOT_FOUND'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test attempting to delete a non-event node.
   *
   * Passing a topic UUID returns EVENT_NOT_FOUND and the topic is unchanged.
   */
  public function testDeleteNonEventNode(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic',
      'field_topic_type' => $topicType->id(),
      'field_content_visibility' => 'public',
      'uid' => 2,
      'status' => 1,
    ]);
    $topic->save();

    $topic_uuid = $topic->uuid();

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => ['EVENT_NOT_FOUND'],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Topic must still exist.
    $topic_id = $topic->id();
    assert(is_string($topic_id));
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache([$topic_id]);
    $loaded_node = $node_storage->load($topic_id);
    $this->assertNotNull($loaded_node, 'Topic should still exist.');
  }

  /**
   * Test that deleting an event also removes related comments.
   *
   * Verifies cascade delete behavior: when an event is deleted, comments
   * are automatically deleted by Drupal's entity implementation.
   */
  public function testDeleteEventWithComments(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $event = $this->createEventNode(['title' => 'Test Event with Comments']);

    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $event->id(),
      'field_name' => 'field_event_comments',
      'comment_type' => 'comment',
      'subject' => 'Test Comment',
      'comment_body' => [
        'value' => 'This is a test comment',
        'format' => 'plain_text',
      ],
      'uid' => 2,
      'status' => 1,
    ]);
    $comment->save();

    $event_uuid = $event->uuid();
    assert(is_string($event_uuid));
    $comment_id = $comment->id();

    $this->assertResults(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event_uuid,
        ],
      ],
      [
        'deleteEvent' => [
          'errors' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Event and its comments must be removed.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $comment_storage = $this->container->get('entity_type.manager')->getStorage('comment');
    $node_storage->resetCache();
    $comment_storage->resetCache();

    $entity_repository = $this->container->get('entity.repository');
    $loaded_event = $entity_repository->loadEntityByUuid('node', $event_uuid);
    $this->assertNull($loaded_event, 'Event should be deleted.');

    $loaded_comment = $comment_storage->load($comment_id);
    $this->assertNull($loaded_comment, 'Comment should be deleted when event is deleted.');
  }

  /**
   * Test that deleting an event requires the event:write scope.
   */
  public function testDeleteEventRequiresEventWriteScope(): void {
    $this->actAsClientCredentialsWithScopes([]);

    $event = $this->createEventNode(['title' => 'Test Event']);
    $event_uuid = $event->uuid();

    $this->assertErrors(
      <<<GQL
        mutation DeleteEvent(\$input: DeleteEventInput!) {
          deleteEvent(input: \$input) {
            errors
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event_uuid,
        ],
      ],
      [
        "Missing scope 'event:write' on 'deleteEvent'.",
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

}
