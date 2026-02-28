<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the updateEvent GraphQL mutation.
 *
 * @group social_event
 */
class UpdateEventMutationTest extends SocialGraphQLTestBase {

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
   * Reloads an event node from storage to assert on fresh entity data.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node to reload.
   *
   * @return \Drupal\node\NodeInterface
   *   The reloaded node (never null; asserts on failure).
   */
  protected function reloadEvent(NodeInterface $node): NodeInterface {
    $id = $node->id();
    assert($id !== NULL);
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([$id]);
    $reloaded = $storage->load($id);
    assert($reloaded !== NULL);
    return $reloaded;
  }

  /**
   * Test updating an event with all fields.
   */
  public function testUpdateEventSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType('Conference');
    $newEventType = $this->createEventType('Workshop');
    $event = $this->createEvent($eventType);

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';
    $newStartTimestamp = (new \DateTimeImmutable('2026-07-01T09:00:00Z'))->getTimestamp();
    $newEndTimestamp = (new \DateTimeImmutable('2026-07-01T17:00:00Z'))->getTimestamp();

    // @todo add visibility and address in the assertResults once it lands in
    // the read schema.
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
          'address' => [
            'countryCode' => 'NL',
            'locality' => 'Amsterdam',
            'postalCode' => '1012 AB',
            'addressLine1' => 'Dam Square 1',
          ],
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

      // @todo use assertResults once we have address as part of the read
      // schema.
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($newEventType)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test clearing the event type by sending type: null.
   */
  public function testUpdateEventClearEventType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType('Conference');
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
              title
              eventType {
                id
              }
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'type' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Original Event Title',
            'eventType' => NULL,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test partial update -- only title.
   */
  public function testUpdateEventTitle(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
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
          'id' => $event->uuid(),
          'title' => 'Updated Title Only',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Updated Title Only',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating an event's body with Rich Text JSON.
   */
  public function testUpdateEventWithBody(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType('Article');
    $event = $this->createEvent($eventType, [
      'body' => [['value' => 'Original body', 'format' => 'basic_html']],
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
              bodyHtml
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . $event->id(), 'config:filter.format.basic_html'])
        ->addCacheContexts(['languages:language_interface'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test no-op update -- only id provided, event returned unchanged.
   *
   * Verifies that when no fields are updated, the node is not saved and no
   * new revision is created.
   */
  public function testUpdateEventNoOp(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $revision_id_before = $event->getRevisionId();
    $changed_before = $event->getChangedTime();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
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
          'id' => $event->uuid(),
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Original Event Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // We verify with a reload that no new revision was created and the changed
    // time is unchanged.
    $reloaded = $this->reloadEvent($event);
    $this->assertSame($revision_id_before, $reloaded->getRevisionId(), 'No new revision should be created when no fields are updated.');
    $this->assertSame($changed_before, $reloaded->getChangedTime(), 'Node changed time should be unchanged when no fields are updated.');
  }

  /**
   * Test validation error for non-existent event ID.
   */
  public function testUpdateEventNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
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
        'updateEvent' => [
          'errors' => ['EVENT_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for empty title.
   */
  public function testUpdateEventEmptyTitle(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => '',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['TITLE_REQUIRED'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for whitespace-only title.
   */
  public function testUpdateEventWhitespaceTitle(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => '   ',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['TITLE_REQUIRED'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testUpdateEventTitleTooLong(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $longTitle = str_repeat('A', 256);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => $longTitle,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['TITLE_TOO_LONG'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid event type UUID.
   */
  public function testUpdateEventInvalidEventType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'type' => $fakeUuid,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['EVENT_TYPE_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid date types (non-integer).
   */
  public function testUpdateEventInvalidDateType(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'startDate' => 1781881200.0,
          'endDate' => 1781967600.0,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => [
            'START_DATE_INVALID',
            'END_DATE_INVALID',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when end date is before start date (both provided).
   */
  public function testUpdateEventEndDateBeforeStartDate(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $startTimestamp = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();
    $endTimestamp = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'startDate' => $startTimestamp,
          'endDate' => $endTimestamp,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['END_DATE_BEFORE_START_DATE'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test cross-field date validation: new startDate after existing endDate.
   */
  public function testUpdateEventStartDateAfterExistingEndDate(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    // Event ends at 2026-06-15T18:00:00.
    $event = $this->createEvent($eventType);

    // Set start date to after the existing end date.
    $newStartTimestamp = (new \DateTimeImmutable('2026-06-16T10:00:00Z'))->getTimestamp();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'startDate' => $newStartTimestamp,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['END_DATE_BEFORE_START_DATE'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating location to a new value.
   */
  public function testUpdateEventLocation(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'field_event_location' => 'Original Location',
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
              location
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'location' => 'New Location',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'location' => 'New Location',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test clearing location by providing NULL.
   */
  public function testUpdateEventClearLocation(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'field_event_location' => 'Original Location',
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
              location
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'location' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'location' => NULL,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that when address is omitted, the event address remains unchanged.
   */
  public function testUpdateEventAddressOmittedUnchanged(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'title' => 'Event With Address To Keep',
      'field_event_address' => [
        [
          'country_code' => 'NL',
          'administrative_area' => '',
          'locality' => 'Amsterdam',
          'dependent_locality' => '',
          'postal_code' => '1012 AB',
          'address_line1' => 'Dam Square 1',
          'address_line2' => '',
        ],
      ],
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
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
          'id' => $event->uuid(),
          'title' => 'Updated Title Only',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Updated Title Only',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have address as part of the read schema.
    $updated = $this->reloadEvent($event);
    $this->assertFalse($updated->get('field_event_address')->isEmpty());
    $address_item = $updated->get('field_event_address')->first();
    $this->assertInstanceOf(AddressItem::class, $address_item);

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address_item */
    $this->assertSame('NL', $address_item->getCountryCode());
    $this->assertSame('Amsterdam', $address_item->getLocality());
    $this->assertSame('Dam Square 1', $address_item->getAddressLine1());
  }

  /**
   * Test that when address is null, the address field is cleared.
   */
  public function testUpdateEventClearAddress(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'field_event_address' => [
        [
          'country_code' => 'NL',
          'administrative_area' => '',
          'locality' => 'Amsterdam',
          'dependent_locality' => '',
          'postal_code' => '1012 AB',
          'address_line1' => 'Dam Square 1',
          'address_line2' => '',
        ],
      ],
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'address' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have address as part of the read schema.
    $updated = $this->reloadEvent($event);
    $this->assertTrue($updated->get('field_event_address')->isEmpty());
  }

  /**
   * Test that when address is provided with valid AddressInput, it is updated.
   */
  public function testUpdateEventAddressUpdated(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'field_event_address' => [
        [
          'country_code' => 'NL',
          'administrative_area' => '',
          'locality' => 'Amsterdam',
          'dependent_locality' => '',
          'postal_code' => '1012 AB',
          'address_line1' => 'Original',
          'address_line2' => '',
        ],
      ],
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'address' => [
            'countryCode' => 'BE',
            'locality' => 'Brussels',
            'postalCode' => '1000',
            'addressLine1' => 'Grand Place 1',
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have address as part of the read schema.
    $updated = $this->reloadEvent($event);
    $this->assertFalse($updated->get('field_event_address')->isEmpty());
    $address_item = $updated->get('field_event_address')->first();
    $this->assertInstanceOf(AddressItem::class, $address_item);
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address_item */
    $this->assertSame('BE', $address_item->getCountryCode());
    $this->assertSame('Brussels', $address_item->getLocality());
    $this->assertSame('Grand Place 1', $address_item->getAddressLine1());
  }

  /**
   * Test that an invalid address country code is rejected.
   */
  public function testUpdateEventAddressInvalidEmptyCountryCode(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType, [
      'field_event_address' => [
        [
          'country_code' => 'NL',
          'administrative_area' => '',
          'locality' => 'Amsterdam',
          'dependent_locality' => '',
          'postal_code' => '1012 AB',
          'address_line1' => 'Original',
          'address_line2' => '',
        ],
      ],
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'address' => [
            'countryCode' => '',
            'locality' => 'Amsterdam',
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => [
            'ADDRESS_COUNTRY_CODE_REQUIRED',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );

    // @todo use assertResults once we have address as part of the read schema.
    // Address should remain unchanged (event not returned on validation error).
    $reloaded = $this->reloadEvent($event);
    $address_item = $reloaded->get('field_event_address')->first();
    $this->assertInstanceOf(AddressItem::class, $address_item);

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address_item */
    $this->assertSame('NL', $address_item->getCountryCode());
  }

  /**
   * Test that invalid country code is rejected with validation errors.
   */
  public function testUpdateEventAddressInvalidCountryCode(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'address' => [
            'countryCode' => 'XX',
            'locality' => 'Somewhere',
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => [
            'ADDRESS_COUNTRY_CODE_INVALID',
          ],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test that field_event_enroll is not changed during update.
   */
  public function testUpdateEventEnrollmentUnchanged(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $original_enroll = $event->get('field_event_enroll')->getString();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
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
          'id' => $event->uuid(),
          'title' => 'Updated Title',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'Updated Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // The enrollment value is not part of the GraphQL schema, so we need to
    // reload the event to check if it changed.
    $updated_event = $this->reloadEvent($event);
    $this->assertEquals($original_enroll, $updated_event->get('field_event_enroll')->getString(), 'Enrollment value should not change during update.');
  }

  /**
   * Test updating an event to public visibility.
   *
   * Event type does not expose visibility in GraphQL; we assert via the node.
   */
  public function testUpdateEventPublicVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'visibility' => 'PUBLIC',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo remove this once we have visibility as part of the read schema.
    $reloaded_event = $this->reloadEvent($event);
    $this->assertEquals('public', $reloaded_event->get('field_content_visibility')->value);
  }

  /**
   * Test updating an event to community visibility.
   *
   * Event type does not expose visibility in GraphQL; we assert via the node.
   */
  public function testUpdateEventCommunityVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    // Create the event with public visibility so the mutation actually changes
    // it.
    $event = $this->createEvent($eventType, [
      'field_content_visibility' => 'public',
    ]);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'visibility' => 'COMMUNITY',
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo remove this once we have visibility as part of the read schema.
    $reloaded_event = $this->reloadEvent($event);
    $this->assertEquals('community', $reloaded_event->get('field_content_visibility')->value);
  }

  /**
   * Test that updating an event requires the event:write scope.
   */
  public function testUpdateEventRequiresEventWriteScope(): void {
    $this->actAsClientCredentialsWithScopes([]);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    $this->assertErrors(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => 'Should Not Work',
        ],
      ],
      [
        "Missing scope 'event:write' on 'updateEvent'.",
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

}
