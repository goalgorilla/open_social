<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the createEvent GraphQL mutation.
 *
 * @group social_event
 */
class CreateEventMutationTest extends SocialGraphQLTestBase {

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
    $this->installEntitySchema('message');
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

    $this->setUpCurrentUser(
      ["uid" => 1],
      [
        'create event content',
        'access content',
        'bypass node access',
        'administer nodes',
        'access cross-group posting',
      ],
      FALSE
    );

    // Enable cross-posting for tests.
    $this->config('social_group.settings')
      ->set('cross_posting.status', TRUE)
      ->set('cross_posting.content_types', ['event'])
      ->set('cross_posting.group_types', ['flexible_group'])
      ->save();
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
   * Returns minimal event timestamps for group tests.
   *
   * @return array{0: int, 1: int}
   *   [startTimestamp, endTimestamp].
   */
  private static function eventTimestamps(): array {
    $start = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $end = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();
    return [$start, $end];
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
   * Creates a test flexible group and saves it.
   *
   * @param string $label
   *   The group label.
   * @param array $field_group_allowed_visibility
   *   Allowed visibility values (e.g. ['public', 'community']).
   *
   * @return \Drupal\group\Entity\Group
   *   The created group.
   */
  protected function createTestGroup(string $label = 'Test Group', array $field_group_allowed_visibility = ['public', 'community']): Group {
    $group = Group::create([
      'type' => 'flexible_group',
      'label' => $label,
      'field_group_allowed_visibility' => $field_group_allowed_visibility,
    ]);
    $group->save();
    return $group;
  }

  /**
   * Counts event nodes with the given title (no access check, for assertions).
   *
   * @param string $title
   *   The event title to count.
   *
   * @return int
   *   The number of matching event nodes.
   */
  private function getEventCountByTitle(string $title): int {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    return (int) $query
      ->condition('type', 'event')
      ->condition('title', $title)
      ->count()
      ->execute();
  }

  /**
   * Loads the first event node with the given title (no access check).
   *
   * @param string $title
   *   The event title.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node or NULL if none found.
   */
  private function getEventByTitle(string $title): ?NodeInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $ids = $query
      ->condition('type', 'event')
      ->condition('title', $title)
      ->range(0, 1)
      ->execute();
    if (!is_array($ids) || empty($ids)) {
      return NULL;
    }
    $node = $storage->load(reset($ids));
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Test creating an event with all required fields.
   */
  public function testCreateEventSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    // @todo add visibility in the assertResults once it lands in the read
    // schema.
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
        // @todo Remove max age once https://www.drupal.org/project/simple_oauth/issues/3573262 is fixed.
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test creating an event without an event type (type is optional).
   */
  public function testCreateEventSuccessWithoutEventType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440001';
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
          'title' => 'Event Without Type 2026',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'location' => 'Somewhere',
        ],
      ],
      [
        'createEvent' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'event' => [
            'title' => 'Event Without Type 2026',
            'location' => 'Somewhere',
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
            'startDate' => [
              'timestamp' => $startTimestamp,
            ],
            'endDate' => [
              'timestamp' => $endTimestamp,
            ],
            'eventType' => NULL,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1', 'taxonomy_term_list', 'config:filter.format.basic_html'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test validation error when Rich Text JSON is invalid (missing root).
   */
  public function testCreateEventInvalidRichTextJson(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertErrors(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { id }
          }
        }
      GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Invalid body',
          'visibility' => 'PUBLIC',
          'body' => ['notRoot' => []],
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'Variable "$input" got invalid value {"type":"' . $eventType->uuid() . '","title":"Invalid body","visibility":"PUBLIC","body":{"notRoot":[]},"startDate":' . $startTimestamp . ',"endDate":' . $endTimestamp . '}; Expected type RichTextJSON at value.body; Invalid Rich Text JSON document: Missing required field "root"',
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test creating an event without optional fields.
   */
  public function testCreateEventWithoutOptionalFields(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-07-01T09:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-07-01T17:00:00Z'))->getTimestamp();

    // @todo add visibility in the assertResults once it lands in the read
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
          'type' => $eventType->uuid(),
          'title' => 'Simple Event',
          'visibility' => 'COMMUNITY',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'clientMutationId' => NULL,
          'errors' => NULL,
          'event' => [
            'title' => 'Simple Event',
            'location' => NULL,
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
   * Test creating an event with public visibility.
   *
   * Event type does not expose visibility in GraphQL; we assert via the node.
   */
  public function testCreateEventPublicVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-08-01T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-08-01T18:00:00Z'))->getTimestamp();
    $visibility = 'PUBLIC';
    $title = "Event with $visibility visibility";

    // @todo add visibility in the assertResults once it lands in the read
    // schema.
    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
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
          'type' => $eventType->uuid(),
          'title' => $title,
          'visibility' => $visibility,
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'title' => $title,
            'location' => NULL,
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
    $node = $this->getEventByTitle($title);
    $this->assertNotNull($node);
    $this->assertEquals('public', $node->get('field_content_visibility')->value);
  }

  /**
   * Test creating an event with community visibility.
   *
   * Event type does not expose visibility in GraphQL; we assert via the node.
   */
  public function testCreateEventCommunityVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-08-01T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-08-01T18:00:00Z'))->getTimestamp();
    $visibility = 'COMMUNITY';
    $title = "Event with $visibility visibility";

    // @todo add visibility in the assertResults once it lands in the read
    // schema.
    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
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
          'type' => $eventType->uuid(),
          'title' => $title,
          'visibility' => $visibility,
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'title' => $title,
            'location' => NULL,
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
    $node = $this->getEventByTitle($title);
    $this->assertNotNull($node);
    $this->assertEquals('community', $node->get('field_content_visibility')->value);
  }

  /**
   * Test validation error when title is empty or whitespace-only (trimmed).
   *
   * The GraphQL schema requires title: String!; presence is enforced there.
   * This test covers application-level validation: non-empty string that
   * becomes empty after trim yields TITLE_REQUIRED.
   */
  public function testCreateEventEmptyOrWhitespaceOnlyTitleFails(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => '   ',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'TITLE_REQUIRED',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('   '), 'No event when title is whitespace-only.');
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testCreateEventTitleTooLong(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();
    $longTitle = str_repeat('A', 256);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => $longTitle,
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'TITLE_TOO_LONG',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle($longTitle), 'No event node should have been created when title is too long.');
  }

  /**
   * Test validation error when start and end date are floats (is_int() guard).
   */
  public function testCreateEventInvalidDateType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { id }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Invalid Date Floats',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => 1781881200.0,
          'endDate' => 1781967600.0,
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'START_DATE_REQUIRED',
            'END_DATE_REQUIRED',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Invalid Date Floats'), 'No event when dates are floats.');
  }

  /**
   * Test validation error when end date is before start date.
   */
  public function testCreateEventEndDateBeforeStartDate(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'End Before Start Test',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'END_DATE_BEFORE_START_DATE',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('End Before Start Test'), 'No event node should have been created when end date is before start date.');
  }

  /**
   * Test validation error for invalid event type UUID.
   */
  public function testCreateEventInvalidEventType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $fakeUuid = '12345678-1234-1234-1234-123456789012';
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $fakeUuid,
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'EVENT_TYPE_NOT_FOUND',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Test Event'), 'No event node should have been created when event type is invalid.');
  }

  /**
   * Test creating an event with a valid address.
   */
  public function testCreateEventWithValidAddress(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
              title
              address {
                countryCode
                locality
                postalCode
                addressLine1
              }
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Event With Address 2026',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'address' => [
            'countryCode' => 'NL',
            'locality' => 'Amsterdam',
            'postalCode' => '1012 AB',
            'addressLine1' => 'Dam Square 1',
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => TRUE,
            'title' => 'Event With Address 2026',
            'address' => [
              'countryCode' => 'NL',
              'locality' => 'Amsterdam',
              'postalCode' => '1012 AB',
              'addressLine1' => 'Dam Square 1',
            ],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:1'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test validation error when address is provided but countryCode is empty.
   *
   * GraphQL schema requires countryCode: String! so it cannot be omitted; we
   * send an empty string to trigger input-level validation.
   */
  public function testCreateEventAddressMissingCountryCode(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { id }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Event Address No Country',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'address' => [
            'countryCode' => '',
            'locality' => 'Amsterdam',
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'ADDRESS_COUNTRY_CODE_REQUIRED',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Address No Country'), 'No event when address has no countryCode.');
  }

  /**
   * Test validation error when address countryCode is not in the list.
   */
  public function testCreateEventAddressInvalidCountryCode(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { id }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Event Invalid Country Code',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
          'address' => [
            'countryCode' => 'XX',
            'locality' => 'Somewhere',
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => [
            'ADDRESS_COUNTRY_CODE_INVALID',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Invalid Country Code'), 'No event when address countryCode is invalid.');
  }

  /**
   * Test creating an event with a single group successfully.
   */
  public function testCreateEventWithSingleGroupSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group = $this->createTestGroup();

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Event in Group',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'title' => 'Event in Group',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:1'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $event = $node_storage->load(1);
    assert($event instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($event);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group->id(), $group_ids, 'Event should be linked to the group.');
  }

  /**
   * Test creating an event with cross-posting successfully.
   */
  public function testCreateEventWithCrossPostingSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group1 = $this->createTestGroup('Group 1');
    $group2 = $this->createTestGroup('Group 2', ['public', 'group']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Cross-posted Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'title' => 'Cross-posted Event',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:1'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $event = $node_storage->load(1);
    assert($event instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($event);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group1->id(), $group_ids, 'Event should be linked to group 1.');
    $this->assertContains($group2->id(), $group_ids, 'Event should be linked to group 2.');
  }

  /**
   * Test validation error when primary group doesn't exist.
   */
  public function testCreateEventWithInvalidPrimaryGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();
    $fakeGroupUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $fakeGroupUuid,
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['GROUP_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when cross-posted group doesn't exist.
   */
  public function testCreateEventWithInvalidCrossPostedGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group = $this->createTestGroup('Test Group', ['public']);

    $fakeGroupUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [$fakeGroupUuid],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['CROSSPOSTED_GROUP_NOT_FOUND:' . $fakeGroupUuid],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when same group is in primary and cross-posted.
   */
  public function testCreateEventWithDuplicateGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group = $this->createTestGroup('Test Group', ['public']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [$group->uuid()],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['GROUP_DUPLICATE'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when too many cross-posted groups.
   */
  public function testCreateEventTooManyCrossPostedGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $primaryGroup = $this->createTestGroup('Primary Group', ['public']);

    $crosspostedGroups = [];
    for ($i = 0; $i < 51; $i++) {
      $group = $this->createTestGroup('Cross-posted Group ' . $i, ['public']);
      $crosspostedGroups[] = $group->uuid();
    }

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $primaryGroup->uuid(),
            'crosspostedGroups' => $crosspostedGroups,
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['LIMIT_EXCEEDED_FOR_CROSSPOSTED_GROUPS'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when visibility is not allowed in single group.
   */
  public function testCreateEventVisibilityNotAllowedInSingleGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group = $this->createTestGroup('Test Group', ['public', 'group']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'COMMUNITY',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation when visibility is not in intersection for cross-posting.
   */
  public function testCreateEventVisibilityNotAllowedInCrossPosting(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group1 = $this->createTestGroup('Group 1');
    $group2 = $this->createTestGroup('Group 2', ['public', 'group']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'COMMUNITY',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when creating a PUBLIC event in a secret group.
   *
   * PROD-34790: Public events are not allowed in groups that do not allow
   * public content visibility. Uses a group with only group visibility allowed
   * (no 'public' or 'community').
   */
  public function testCreateEventPublicNotAllowedInSecretGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $group = $this->createTestGroup('Secret Group', ['group']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when cross-posting PUBLIC event with a secret group.
   *
   * PROD-34790: If any group (primary or cross-posted) does not allow public
   * visibility, creating a PUBLIC event fails with
   * VISIBILITY_NOT_ALLOWED_IN_GROUP.
   */
  public function testCreateEventPublicNotAllowedInSecretGroupCrossPosting(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    [$start, $end] = self::eventTimestamps();

    $secretGroup = $this->createTestGroup('Secret Group', ['community', 'group']);
    $publicGroup = $this->createTestGroup('Public Group', ['public', 'group']);

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $secretGroup->uuid(),
            'crosspostedGroups' => [$publicGroup->uuid()],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when cross-posting is disabled.
   */
  public function testCreateEventCrossPostingDisabled(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $group_settings = $this->config('social_group.settings');
    $previous_status = $group_settings->get('cross_posting.status');
    $previous_content_types = $group_settings->get('cross_posting.content_types');
    $previous_group_types = $group_settings->get('cross_posting.group_types');

    try {
      $this->config('social_group.settings')
        ->set('cross_posting.status', FALSE)
        ->save();

      [$start, $end] = self::eventTimestamps();

      $group1 = $this->createTestGroup('Group 1', ['public']);
      $group2 = $this->createTestGroup('Group 2', ['public']);

      $this->assertResults(
        <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
        [
          'input' => [
            'title' => 'Test Event',
            'visibility' => 'PUBLIC',
            'body' => self::minimalRichTextBody(),
            'startDate' => $start,
            'endDate' => $end,
            'groups' => [
              'group' => $group1->uuid(),
              'crosspostedGroups' => [$group2->uuid()],
            ],
          ],
        ],
        [
          'createEvent' => [
            'errors' => ['CROSS_POSTING_IS_DISABLED'],
            'event' => NULL,
          ],
        ],
        $this->defaultMutationCacheMetaData()
          ->addCacheContexts(['languages:language_interface'])
      );
    }
    finally {
      $this->config('social_group.settings')
        ->set('cross_posting.status', $previous_status)
        ->set('cross_posting.content_types', $previous_content_types)
        ->set('cross_posting.group_types', $previous_group_types)
        ->save();
    }
  }

  /**
   * Test that creating an event requires the event:write scope.
   */
  public function testCreateEventRequiresEventWriteScope(): void {
    $this->actAsClientCredentialsWithScopes([]);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertErrors(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        "Missing scope 'event:write' on 'createEvent'.",
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Test Event'), 'No event node should have been created when event:write scope is missing.');
  }

  /**
   * Test that authorization_code grant is rejected by @allowBot.
   */
  public function testCreateEventRejectsAuthorizationCodeGrant(): void {
    $user = $this->createUser([
      'create event content',
      'access content',
      'grant simple_oauth codes',
    ]);
    $this->assertNotFalse($user, 'User should be created');
    $this->actAsAuthorizationCodeWithScopes(['event:write'], $user);

    $eventType = $this->createEventType();
    $startTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertErrors(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'type' => $eventType->uuid(),
          'title' => 'Test Event',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        "Application type 'User' does not have access on 'createEvent'.",
      ],
      $this->defaultMutationCacheMetaData()
    );
    $this->assertSame(0, $this->getEventCountByTitle('Test Event'), 'No event node should have been created when authorization_code grant is used with @allowBot.');
  }

}
