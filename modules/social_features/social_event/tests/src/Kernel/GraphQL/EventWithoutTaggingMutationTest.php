<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the event mutations without tagging.
 *
 * @group social_event
 */
class EventWithoutTaggingMutationTest extends SocialGraphQLTestBase {

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
   * Helper to create a valid event type taxonomy term.
   *
   * @param string $name
   *   The name of the event type.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created term.
   */
  protected function createEventType(string $name = 'Conference'): Term {
    $term = Term::create([
      'vid' => 'event_types',
      'name' => $name,
    ]);
    $term->save();
    return $term;
  }

  /**
   * Helper to create a test event node for use in update tests.
   *
   * @param \Drupal\taxonomy\Entity\Term $event_type
   *   The event type term.
   * @param array $overrides
   *   Optional field overrides.
   *
   * @return \Drupal\node\NodeInterface
   *   The created event node.
   */
  protected function createEvent(Term $event_type, array $overrides = []): NodeInterface {
    $values = array_merge([
      'type' => 'event',
      'title' => 'Original Event Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_event_type' => $event_type->id(),
      'field_event_date' => '2026-06-15T10:00:00',
      'field_event_date_end' => '2026-06-15T18:00:00',
      'field_event_enroll' => 0,
      'status' => 1,
    ], $overrides);

    $node = Node::create($values);
    $node->save();
    return $node;
  }

  /**
   * Test creating an event with all required fields (without tagging).
   */
  public function testCreateEventSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            clientMutationId
            errors
            event {
              title
              location
              bodyHtml
              startDate {
                timestamp
              }
              endDate {
                timestamp
              }
              eventType {
                id
              }
            }
          }
        }
        GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'type' => $eventType->uuid(),
          'title' => 'Annual Conference 2026',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'location' => 'Amsterdam Convention Centre',
        ],
      ],
      [
        'createEvent' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'event' => [
            'title' => 'Annual Conference 2026',
            'location' => 'Amsterdam Convention Centre',
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
            'eventType' => [
              'id' => $eventType->uuid(),
            ],
            'startDate' => [
              'timestamp' => $startTimestamp,
            ],
            'endDate' => [
              'timestamp' => $endTimestamp,
            ],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1', 'taxonomy_term:1', 'config:filter.format.basic_html'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test that createEvent rejects contentTags when tagging is not enabled.
   */
  public function testCreateEventWithTaggingErrors(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertErrors(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          clientMutationId
          errors
          event {
            title
            eventType { id }
            bodyHtml
          }
        }
      }
      GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'type' => $eventType->uuid(),
          'title' => 'Annual Conference 2026',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'location' => 'Amsterdam',
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","type":"' . $eventType->uuid() . '","title":"Annual Conference 2026","visibility":"PUBLIC","body":{"root":{"type":"root","version":1,"children":[{"type":"paragraph","version":1,"children":[{"type":"text","version":1,"text":"Hello"}]}]}},"startDate":' . $startTimestamp . ',"endDate":' . $endTimestamp . ',"location":"Amsterdam","contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type CreateEventInput.',
      ],
      $this->defaultMutationCacheMetaData()
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test updating an event with all fields (without tagging).
   */
  public function testUpdateEventSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType('Conference');
    $newEventType = $this->createEventType('Workshop');
    $event = $this->createEvent($eventType);

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $newStartTimestamp = (new \DateTimeImmutable('2026-07-01T09:00:00Z'))->getTimestamp();
    $newEndTimestamp = (new \DateTimeImmutable('2026-07-01T17:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            clientMutationId
            errors
            event {
              id
              title
              location
              startDate {
                timestamp
              }
              endDate {
                timestamp
              }
              eventType {
                id
              }
            }
          }
        }
        GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $event->uuid(),
          'type' => $newEventType->uuid(),
          'title' => 'Updated Event Title',
          'visibility' => 'PUBLIC',
          'startDate' => $newStartTimestamp,
          'endDate' => $newEndTimestamp,
          'location' => 'Amsterdam Convention Centre',
        ],
      ],
      [
        'updateEvent' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Updated Event Title',
            'location' => 'Amsterdam Convention Centre',
            'startDate' => [
              'timestamp' => $newStartTimestamp,
            ],
            'endDate' => [
              'timestamp' => $newEndTimestamp,
            ],
            'eventType' => [
              'id' => $newEventType->uuid(),
            ],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($newEventType)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that updateEvent rejects contentTags when tagging is not enabled.
   */
  public function testUpdateEventWithTaggingErrors(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->assertErrors(
      <<<GQL
      mutation UpdateEvent(\$input: UpdateEventInput!) {
        updateEvent(input: \$input) {
          clientMutationId
          errors
          event {
            id
            title
          }
        }
      }
      GQL,
      [
        'input' => [
          'clientMutationId' => $clientMutationId,
          'id' => $event->uuid(),
          'title' => 'Updated Event Title',
          'contentTags' => [$clientMutationId],
        ],
      ],
      [
        'Variable "$input" got invalid value {"clientMutationId":"' . $clientMutationId . '","id":"' . $event->uuid() . '","title":"Updated Event Title","contentTags":["' . $clientMutationId . '"]}; Field "contentTags" is not defined by type UpdateEventInput.',
      ],
      $this->defaultMutationCacheMetaData()
        ->setCacheMaxAge(0)
    );
  }

}
