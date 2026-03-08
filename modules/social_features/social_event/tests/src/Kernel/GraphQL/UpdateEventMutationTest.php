<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\social_group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the updateEvent GraphQL mutation.
 *
 * Organization-related tests are skipped when social_organization is not
 * available (e.g. open source distribution).
 *
 * @group social_event
 */
class UpdateEventMutationTest extends SocialEventGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   *
   * Include social_organization so the kernel can boot with it when present
   * (avoids ConfigSchemaAlterException). Removed in setUpBeforeClass() when
   * the module is not in the codebase.
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
    'social_event_type',
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
   * Whether social_organization is available and was enabled for this test.
   *
   * @var bool
   */
  protected bool $organizationAvailable = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    if (!static::socialOrganizationExists()) {
      static::$modules = array_values(array_filter(
        static::$modules,
        static fn(string $m): bool => $m !== 'social_organization'
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigToInstall(): array {
    $config = parent::getConfigToInstall();
    if ($this->container->get('module_handler')->moduleExists('social_organization')) {
      $config[] = 'social_organization';
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->organizationAvailable = $this->container->get('module_handler')->moduleExists('social_organization');
    if ($this->organizationAvailable) {
      $this->installOrganizationRequiredViews();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
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
          'body' => $this->minimalRichTextBody(),
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
              address {
                countryCode
                locality
                addressLine1
              }
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
            'address' => [
              'countryCode' => 'NL',
              'locality' => 'Amsterdam',
              'addressLine1' => 'Dam Square 1',
            ],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
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
              address {
                countryCode
                locality
                addressLine1
              }
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
            'address' => [
              'countryCode' => 'BE',
              'locality' => 'Brussels',
              'addressLine1' => 'Grand Place 1',
            ],
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );
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
   * Test adding an event to a group via update.
   */
  public function testUpdateEventAddToGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $group = $this->createTestGroup();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'groups' => [
            'group' => $group->uuid(),
          ],
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

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $group_ids = array_map(fn ($r) => (string) $r->getGroup()->id(), $relationships);
    $this->assertContains((string) $group->id(), $group_ids, 'Event should be linked to the group.');
  }

  /**
   * Test removing an event from all groups via update.
   */
  public function testUpdateEventRemoveFromAllGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $group = $this->createTestGroup();

    $event = $this->createEvent($eventType);
    $this->container->get('social_group.set_groups_for_node_service')
      ->setGroupsForNode($event, [], [$group->id() => $group->id()], [], FALSE);
    $event->set('groups', [['target_id' => $group->id()]]);
    $event->save();

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
          'groups' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $this->assertEmpty($relationships, 'Event should not be in any group.');
  }

  /**
   * Test changing an event's groups via update.
   */
  public function testUpdateEventChangeGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $group1 = $this->createTestGroup('Group 1');
    $group2 = $this->createTestGroup('Group 2');

    $event = $this->createEvent($eventType);
    $this->container->get('social_group.set_groups_for_node_service')
      ->setGroupsForNode($event, [], [$group1->id() => $group1->id()], [], FALSE);
    $event->set('groups', [['target_id' => $group1->id()]]);
    $event->save();

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
          'groups' => [
            'group' => $group2->uuid(),
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $group_ids = array_map(fn ($r) => (string) $r->getGroup()->id(), $relationships);
    $this->assertContains((string) $group2->id(), $group_ids);
    $this->assertNotContains((string) $group1->id(), $group_ids);
  }

  /**
   * Test that omitting groups leaves group membership unchanged.
   */
  public function testUpdateEventLeaveGroupsUnchanged(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $group = $this->createTestGroup();

    $event = $this->createEvent($eventType);
    $this->container->get('social_group.set_groups_for_node_service')
      ->setGroupsForNode($event, [], [$group->id() => $group->id()], [], FALSE);
    $event->set('groups', [['target_id' => $group->id()]]);
    $event->save();

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
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

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $group_ids = array_map(fn ($r) => (string) $r->getGroup()->id(), $relationships);
    $this->assertContains((string) $group->id(), $group_ids, 'Event should still be in the group.');
  }

  /**
   * Test validation error when group does not exist.
   */
  public function testUpdateEventInvalidGroup(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $fake_group_uuid = '12345678-1234-1234-1234-123456789012';

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
          'groups' => [
            'group' => $fake_group_uuid,
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['GROUP_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when primary and crossposted list duplicate group.
   */
  public function testUpdateEventDuplicateGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $group = $this->createTestGroup();

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
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [$group->uuid()],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['GROUP_DUPLICATE'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating an event with cross-posting (primary + crossposted groups).
   */
  public function testUpdateEventCrossPostingSuccess(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $group1 = $this->createTestGroup('Group 1');
    $group2 = $this->createTestGroup('Group 2');

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
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $group_ids = array_map(fn ($r) => (string) $r->getGroup()->id(), $relationships);
    $this->assertContains((string) $group1->id(), $group_ids);
    $this->assertContains((string) $group2->id(), $group_ids);
  }

  /**
   * Test validation error when visibility is not allowed in target groups.
   */
  public function testUpdateEventVisibilityNotAllowedInGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $group = $this->createTestGroup('Group public only', ['public']);

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
          'visibility' => 'COMMUNITY',
          'groups' => [
            'group' => $group->uuid(),
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that cross-posting is rejected when cross-posting is disabled.
   */
  public function testUpdateEventCrossPostingDisabled(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $group_settings = $this->config('social_group.settings');
    $previous_status = $group_settings->get('cross_posting.status');
    $previous_content_types = $group_settings->get('cross_posting.content_types');
    $previous_group_types = $group_settings->get('cross_posting.group_types');

    try {
      $group_settings
        ->set('cross_posting.status', FALSE)
        ->save();

      $eventType = $this->createEventType();
      $event = $this->createEvent($eventType);
      $group1 = $this->createTestGroup('Group 1');
      $group2 = $this->createTestGroup('Group 2');

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
            'groups' => [
              'group' => $group1->uuid(),
              'crosspostedGroups' => [$group2->uuid()],
            ],
          ],
        ],
        [
          'updateEvent' => [
            'errors' => ['CROSS_POSTING_IS_DISABLED'],
            'event' => NULL,
          ],
        ],
        $this->defaultMutationCacheMetaData()
          ->addCacheContexts(['languages:language_interface'])
      );
    }
    finally {
      $group_settings
        ->set('cross_posting.status', $previous_status)
        ->set('cross_posting.content_types', $previous_content_types)
        ->set('cross_posting.group_types', $previous_group_types)
        ->save();
    }
  }

  /**
   * Test updating event from single group to primary + crossposted.
   */
  public function testUpdateEventSingleToMultipleGroups(): void {
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $group1 = $this->createTestGroup('Group 1');
    $group2 = $this->createTestGroup('Group 2');

    $event = $this->createEvent($eventType);
    $this->container->get('social_group.set_groups_for_node_service')
      ->setGroupsForNode($event, [], [$group1->id() => $group1->id()], [], FALSE);
    $event->set('groups', [['target_id' => $group1->id()]]);
    $event->save();

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
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have groups as part of the read schema.
    $relationships = GroupRelationship::loadByEntity($this->reloadEvent($event));
    $group_ids = array_map(fn ($r) => (string) $r->getGroup()->id(), $relationships);
    $this->assertContains((string) $group1->id(), $group_ids);
    $this->assertContains((string) $group2->id(), $group_ids);
  }

  /**
   * Organizations omitted from input leaves event's organizations unchanged.
   */
  public function testUpdateEventOrganizationsOmittedUnchanged(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $orgId = $organization->id();
    assert($orgId !== NULL);

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $orgId]],
    ]);

    // Update title only; do not pass organizations.
    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
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

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Organizations null clears organization associations.
   */
  public function testUpdateEventOrganizationsNullCleared(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $orgId = $organization->id();
    assert($orgId !== NULL);

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $orgId]],
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
          'organizations' => NULL,
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventNotInAnyOrganization($eventId);
  }

  /**
   * Organizations with valid value sets event to that organization.
   */
  public function testUpdateEventOrganizationsSet(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
          }
        }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
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

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    $orgId = $organization->id();
    assert($eventId !== NULL && $orgId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Change event from one organization to another.
   */
  public function testUpdateEventOrganizationsChange(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $org1 = $this->createOrganization('Organization 1', 'public');
    $org2 = $this->createOrganization('Organization 2', 'public');

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $org1->id()]],
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
          'organizations' => [
            'organization' => $org2->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => ['id' => $event->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    $org2Id = $org2->id();
    assert($eventId !== NULL && $org2Id !== NULL);
    $this->assertEventInOrganization($eventId, $org2Id);
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($eventId);
    assert($node instanceof NodeInterface);
    $gids = array_map('strval', array_column($node->get('organizations_group')->getValue(), 'target_id'));
    $this->assertNotContains((string) $org1->id(), $gids, 'Event should no longer be in org1.');
  }

  /**
   * Update with primary and crossposted organizations.
   */
  public function testUpdateEventOrganizationsCrossposted(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $primaryOrg = $this->createOrganization('Primary Organization', 'public');
    $crossOrg1 = $this->createOrganization('Cross Organization 1', 'public');
    $crossOrg2 = $this->createOrganization('Cross Organization 2', 'public');

    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
          }
        }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'organizations' => [
            'organization' => $primaryOrg->uuid(),
            'crosspostedOrganizations' => [
              $crossOrg1->uuid(),
              $crossOrg2->uuid(),
            ],
          ],
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

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    assert($eventId !== NULL);
    $primaryOrgId = $primaryOrg->id();
    $crossOrg1Id = $crossOrg1->id();
    $crossOrg2Id = $crossOrg2->id();
    assert($primaryOrgId !== NULL && $crossOrg1Id !== NULL && $crossOrg2Id !== NULL);
    $this->assertEventInOrganization($eventId, $primaryOrgId);
    $this->assertEventInOrganization($eventId, $crossOrg1Id);
    $this->assertEventInOrganization($eventId, $crossOrg2Id);
  }

  /**
   * Invalid organization UUID returns appropriate violation.
   */
  public function testUpdateEventOrganizationsInvalidOrganizationId(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $fake_uuid = '00000000-0000-0000-0000-000000000000';

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
          'organizations' => [
            'organization' => $fake_uuid,
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test: organizations update combined with another field (e.g. title).
   */
  public function testUpdateEventOrganizationsCombinedWithOtherFields(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $event = $this->createEvent($eventType);

    $this->assertResults(
      <<<GQL
        mutation UpdateEvent(\$input: UpdateEventInput!) {
          updateEvent(input: \$input) {
            errors
            event { id title }
          }
        }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'title' => 'New Title With Orgs',
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => NULL,
          'event' => [
            'id' => $event->uuid(),
            'title' => 'New Title With Orgs',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    $orgId = $organization->id();
    assert($eventId !== NULL && $orgId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Updating to a members-only organization without access is rejected.
   */
  public function testUpdateEventOrganizationsAccessRespected(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);

    // Members-only org; do not add the OAuth actor as member.
    $membersOnlyOrg = Group::create([
      'type' => 'organization',
      'label' => 'Members Only Organization',
      'uid' => 1,
      'field_flexible_group_visibility' => 'members',
      'status' => 1,
    ]);
    assert($membersOnlyOrg->bundle() === 'organization');
    $membersOnlyOrg->save();

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
          'organizations' => [
            'organization' => $membersOnlyOrg->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'updateEvent' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // @todo use assertResults once we have organizations as part of the read
    // schema.
    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventNotInAnyOrganization($eventId);
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
