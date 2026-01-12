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
   * Test creating a topic with all required fields.
   */
  public function testCreateTopicSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Grant permissions to the OAuth Machine user (UID 2)
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole([
        'create topic content',
        'access content',
        'bypass node access',
      ]);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    // Execute mutation.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topicType, $clientMutationId) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation CreateTopic(\$type: ID!, \$title: String!, \$visibility: ContentVisibility!, \$clientMutationId: UUIDv4) {
                createTopic(input: {
                  clientMutationId: \$clientMutationId
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
            'variables' => [
              'clientMutationId' => $clientMutationId,
              'type' => $topicType->uuid(),
              'title' => 'Test Topic',
              'visibility' => 'PUBLIC',
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
    $this->assertEmpty($result->data['createTopic']['errors']);
    $this->assertEquals($clientMutationId, $result->data['createTopic']['clientMutationId']);
    $this->assertNotNull($result->data['createTopic']['topic']);
    $this->assertNotEmpty($result->data['createTopic']['topic']['id']);
    $this->assertEquals('Test Topic', $result->data['createTopic']['topic']['title']);
    $this->assertEquals('PUBLIC', $result->data['createTopic']['topic']['visibility']);

    $topic_id = $result->data['createTopic']['topic']['id'];
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache();
    $entity_repository = \Drupal::service('entity.repository');
    $loaded_node = $entity_repository->loadEntityByUuid('node', $topic_id);
    if (empty($loaded_node)) {
      $this->fail('Is not possible to load the Topic created.');
    }

    $topic_type_entity = $loaded_node->get('field_topic_type')->entity;
    // @phpstan-ignore-next-line
    if ($topic_type_entity !== NULL) {
      $this->assertEquals($topicType->id(), $topic_type_entity->id(), 'Topic type should match');
    }
  }

  /**
   * Test validation error for missing required field (title).
   */
  public function testCreateTopicMissingTitle(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Grant permissions to the OAuth Machine user.
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole(['create topic content', 'access content', 'bypass node access']);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    // Execute mutation without title.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topicType) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation CreateTopic(\$input: CreateTopicInput!) {
                createTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'type' => $topicType->uuid(),
                'title' => '',
                'visibility' => 'PUBLIC',
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['createTopic']['errors']);
    $this->assertContains('TITLE_REQUIRED', $result->data['createTopic']['errors']);
    $this->assertNull($result->data['createTopic']['topic']);
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testCreateTopicTitleTooLong(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Grant permissions to the OAuth Machine user.
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole(['create topic content', 'access content', 'bypass node access']);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    // Create a title longer than 255 characters.
    $longTitle = str_repeat('A', 256);

    // Execute mutation with long title.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topicType, $longTitle) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation CreateTopic(\$input: CreateTopicInput!) {
                createTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'type' => $topicType->uuid(),
                'title' => $longTitle,
                'visibility' => 'PUBLIC',
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['createTopic']['errors']);
    $this->assertContains('TITLE_TOO_LONG', $result->data['createTopic']['errors']);
    $this->assertNull($result->data['createTopic']['topic']);
  }

  /**
   * Test validation error for invalid topic type UUID.
   */
  public function testCreateTopicInvalidTopicType(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Grant permissions to the OAuth Machine user.
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole(['create topic content', 'access content', 'bypass node access']);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    // Execute mutation with invalid topic type.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($fakeUuid) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation CreateTopic(\$input: CreateTopicInput!) {
                createTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'type' => $fakeUuid,
                'title' => 'Test Topic',
                'visibility' => 'PUBLIC',
              ],
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['createTopic']['errors']);
    $this->assertContains('TOPIC_TYPE_NOT_FOUND', $result->data['createTopic']['errors']);
    $this->assertNull($result->data['createTopic']['topic']);
  }

  /**
   * Test creating topic with different visibility levels.
   */
  public function testCreateTopicDifferentVisibilities(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    // Grant permissions to the OAuth Machine user.
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole(['create topic content', 'access content', 'bypass node access']);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $visibilities = ['PUBLIC', 'COMMUNITY'];

    foreach ($visibilities as $visibility) {
      $context = new RenderContext();
      $renderer = \Drupal::service('renderer');
      $result = $renderer->executeInRenderContext(
        $context,
        function () use ($topicType, $visibility) {
          return $this->server->executeOperation(
            OperationParams::create([
              'query' => <<<GQL
                mutation CreateTopic(\$input: CreateTopicInput!) {
                  createTopic(input: \$input) {
                    errors
                    topic {
                      id
                      visibility
                    }
                  }
                }
                GQL,
              'variables' => [
                'input' => [
                  'type' => $topicType->uuid(),
                  'title' => "Topic with $visibility visibility",
                  'visibility' => $visibility,
                ],
              ],
            ])
          );
        }
      );

      $this->assertEmpty($result->errors);
      $this->assertEmpty($result->data['createTopic']['errors']);
      $this->assertNotNull($result->data['createTopic']['topic']);
      $this->assertEquals($visibility, $result->data['createTopic']['topic']['visibility']);
    }
  }

  /**
   * Test that creating a topic requires the topic:write scope.
   */
  public function testCreateTopicRequiresTopicWriteScope(): void {
    // Act as client credentials without the required scope.
    $this->actAsClientCredentialsWithScopes([]);

    // Grant permissions to the OAuth Machine user to ensure the test is
    // specifically checking scope, not permissions.
    $machine_user = \Drupal::entityTypeManager()->getStorage('user')->load(2);
    if ($machine_user) {
      $role = $this->createRole([
        'create topic content',
        'access content',
        'bypass node access',
      ]);
      $machine_user->addRole($role);
      $machine_user->save();
    }

    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Execute mutation without the required scope.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topicType) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation CreateTopic(\$input: CreateTopicInput!) {
                createTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'type' => $topicType->uuid(),
                'title' => 'Test Topic',
                'visibility' => 'PUBLIC',
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

}
