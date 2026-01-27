<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for the deleteTopic GraphQL mutation.
 *
 * @group social_topic
 */
class DeleteTopicMutationTest extends SocialGraphQLTestBase {

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
   * Test deleting a topic successfully.
   */
  public function testDeleteTopicSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create a topic owned by the machine user.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic to Delete',
      'field_topic_type' => $topicType->id(),
      'field_content_visibility' => 'public',
      'uid' => 2,
      'status' => 1,
    ]);
    $topic->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $topic_uuid = $topic->uuid();
    assert(is_string($topic_uuid));

    // Execute mutation.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic_uuid, $clientMutationId) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$id: ID!, \$clientMutationId: UUIDv4) {
                deleteTopic(input: {
                  clientMutationId: \$clientMutationId
                  id: \$id
                }) {
                  clientMutationId
                  errors
                }
              }
              GQL,
            'variables' => [
              'clientMutationId' => $clientMutationId,
              'id' => $topic_uuid,
            ],
          ])
        );
      }
    );

    if (!empty($result->errors)) {
      $errorMessages = [];
      foreach ($result->errors as $error) {
        if (is_object($error) && method_exists($error, 'getMessage')) {
          $errorMessages[] = $error->getMessage();
        }
        elseif (is_array($error)) {
          $errorMessages[] = json_encode($error);
        }
        else {
          $errorMessages[] = (string) $error;
        }
      }
      $this->fail('GraphQL errors found: ' . implode(' | ', $errorMessages));
    }

    $this->assertEmpty($result->errors);
    $this->assertEmpty($result->data['deleteTopic']['errors']);
    $this->assertEquals($clientMutationId, $result->data['deleteTopic']['clientMutationId']);

    // Verify the topic was deleted.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache();
    $entity_repository = \Drupal::service('entity.repository');
    $loaded_node = $entity_repository->loadEntityByUuid('node', $topic_uuid);
    $this->assertNull($loaded_node, 'Topic should be deleted.');
  }

  /**
   * Test validation error for missing topic ID.
   */
  public function testDeleteTopicMissingId(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Execute mutation without ID.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$input: DeleteTopicInput!) {
                deleteTopic(input: \$input) {
                  errors
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => '',
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['deleteTopic']['errors']);
    $this->assertContains('TOPIC_ID_REQUIRED', $result->data['deleteTopic']['errors']);
  }

  /**
   * Test validation error for non-existent topic.
   */
  public function testDeleteTopicNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    // Execute mutation with invalid topic ID.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($fakeUuid) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$input: DeleteTopicInput!) {
                deleteTopic(input: \$input) {
                  errors
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $fakeUuid,
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['deleteTopic']['errors']);
    $this->assertContains('TOPIC_NOT_FOUND', $result->data['deleteTopic']['errors']);
  }

  /**
   * Test that deleting a topic requires the topic:write scope.
   */
  public function testDeleteTopicRequiresTopicWriteScope(): void {
    // Act as client credentials without the required scope.
    $this->actAsClientCredentialsWithScopes([]);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create a topic owned by the machine user.
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

    // Execute mutation without the required scope.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic_uuid) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$input: DeleteTopicInput!) {
                deleteTopic(input: \$input) {
                  errors
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $topic_uuid,
              ],
            ],
          ])
        );
      }
    );

    // Should have GraphQL errors about missing scope.
    $this->assertNotEmpty($result->errors);
    $errorMessages = [];
    foreach ($result->errors as $error) {
      if (is_object($error) && method_exists($error, 'getMessage')) {
        $errorMessages[] = $error->getMessage();
      }
      elseif (is_array($error)) {
        $errorMessages[] = json_encode($error);
      }
      else {
        $errorMessages[] = (string) $error;
      }
    }
    $errorMessage = implode(' | ', $errorMessages);
    $this->assertStringContainsString("Missing scope 'topic:write'", $errorMessage, 'Expected error about missing topic:write scope');
  }

  /**
   * Test attempting to delete a non-topic node.
   */
  public function testDeleteNonTopicNode(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a non-topic node (event).
    $event = Node::create([
      'type' => 'event',
      'title' => 'Test Event',
      'uid' => 2,
      'status' => 1,
    ]);
    $event->save();

    $event_uuid = $event->uuid();

    // Execute mutation - should fail with TOPIC_NOT_FOUND.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($event_uuid) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$input: DeleteTopicInput!) {
                deleteTopic(input: \$input) {
                  errors
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $event_uuid,
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['deleteTopic']['errors']);
    $this->assertContains('TOPIC_NOT_FOUND', $result->data['deleteTopic']['errors']);

    // Verify the event was NOT deleted.
    $event_id = $event->id();
    assert(is_string($event_id));
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache([$event_id]);
    $loaded_node = $node_storage->load($event_id);
    $this->assertNotNull($loaded_node, 'Event should still exist.');
  }

  /**
   * Test that deleting a topic also deletes related comments and files.
   *
   * This test verifies the cascade delete behavior according to Drupal's
   * cleanup mechanisms. When a topic is deleted:
   * - Comments should be deleted via hook_entity_predelete()
   * - Files should be marked as temporary for cleanup via cron.
   */
  public function testDeleteTopicWithCommentsAndFiles(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create a file to attach to the topic.
    $file = File::create([
      'uri' => 'public://test-file.txt',
      'filename' => 'test-file.txt',
      'filemime' => 'text/plain',
      'status' => 1,
    ]);
    // Create the actual file.
    $file_uri = $file->getFileUri();
    assert(is_string($file_uri));
    $file_system = \Drupal::service('file_system');
    $directory = $file_system->dirname($file_uri);
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    file_put_contents($file_uri, 'Test file content');
    $file->save();

    // Create a topic with a file attachment.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic with Files and Comments',
      'field_topic_type' => $topicType->id(),
      'field_content_visibility' => 'public',
      'field_files' => [
        'target_id' => $file->id(),
      ],
      'uid' => 2,
      'status' => 1,
    ]);
    $topic->save();

    // Mark file as used by the topic.
    $topic_id = $topic->id();
    assert(is_string($topic_id));
    \Drupal::service('file.usage')->add($file, 'file', 'node', $topic_id);

    // Create comments on the topic.
    $comment1 = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $topic->id(),
      'field_name' => 'field_topic_comments',
      'comment_type' => 'comment',
      'subject' => 'Test Comment 1',
      'comment_body' => [
        'value' => 'This is test comment 1',
        'format' => 'plain_text',
      ],
      'uid' => 2,
      'status' => 1,
    ]);
    $comment1->save();

    $comment2 = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $topic->id(),
      'field_name' => 'field_topic_comments',
      'comment_type' => 'comment',
      'subject' => 'Test Comment 2',
      'comment_body' => [
        'value' => 'This is test comment 2',
        'format' => 'plain_text',
      ],
      'uid' => 2,
      'status' => 1,
    ]);
    $comment2->save();

    $topic_uuid = $topic->uuid();
    assert(is_string($topic_uuid));
    $comment1_id = $comment1->id();
    $comment2_id = $comment2->id();
    $file_id = $file->id();

    // Verify entities exist before deletion.
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($topic->id()));
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('comment')->load($comment1_id));
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('comment')->load($comment2_id));
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('file')->load($file_id));

    // Execute mutation to delete the topic.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic_uuid) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation DeleteTopic(\$input: DeleteTopicInput!) {
                deleteTopic(input: \$input) {
                  errors
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $topic_uuid,
              ],
            ],
          ])
        );
      }
    );

    // Verify mutation succeeded.
    $this->assertEmpty($result->errors);
    $this->assertEmpty($result->data['deleteTopic']['errors']);

    // Reset caches.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $node_storage->resetCache();
    $comment_storage->resetCache();
    $file_storage->resetCache();

    // Verify the topic was deleted.
    $entity_repository = \Drupal::service('entity.repository');
    $loaded_topic = $entity_repository->loadEntityByUuid('node', $topic_uuid);
    $this->assertNull($loaded_topic, 'Topic should be deleted.');

    // Verify comments were deleted (cascade delete via hook_entity_predelete).
    $loaded_comment1 = $comment_storage->load($comment1_id);
    $loaded_comment2 = $comment_storage->load($comment2_id);
    $this->assertNull($loaded_comment1, 'Comment 1 should be deleted when topic is deleted.');
    $this->assertNull($loaded_comment2, 'Comment 2 should be deleted when topic is deleted.');

    // Verify file still exists but usage was removed.
    $loaded_file = $file_storage->load($file_id);
    $this->assertNotNull($loaded_file, 'File entity should still exist after topic deletion.');

    // Verify file usage was removed (most important check).
    $file_usage = \Drupal::service('file.usage')->listUsage($loaded_file);
    $this->assertEmpty($file_usage, 'File usage should be removed when topic is deleted.');

    // Note: Whether the file is marked as temporary depends on the Drupal
    // configuration setting file.settings:make_unused_managed_files_temporary.
    // The actual file deletion happens via cron (system_cron()).
    // What matters is that the file usage was removed, which we verified above.
  }

}
