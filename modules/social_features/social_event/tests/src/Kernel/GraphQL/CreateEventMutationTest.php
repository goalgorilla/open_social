<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for the createEvent GraphQL mutation.
 *
 * Organization-related tests are skipped when social_organization is not
 * available (e.g. open source distribution).
 *
 * @group social_event
 */
class CreateEventMutationTest extends SocialEventGraphQLKernelTestBase {

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
    // Remove social_organization from the module list when not in the codebase
    // so the kernel can boot.
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

    // If social_organization is available, install the required views.
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();
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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

    [$start, $end] = $this->eventTimestamps();

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
          'body' => $this->minimalRichTextBody(),
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

      [$start, $end] = $this->eventTimestamps();

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
            'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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
          'body' => $this->minimalRichTextBody(),
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

  /**
   * Test validation error when organization UUID is not found.
   */
  public function testCreateEventOrganizationNotFound(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    [$start, $end] = $this->eventTimestamps();

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
          'title' => 'Event in Invalid Org',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => '00000000-0000-0000-0000-000000000000',
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event in Invalid Org'), 'No event when organization is not found.');
  }

  /**
   * Test validation error when primary organization is missing in input.
   */
  public function testCreateEventPrimaryOrganizationRequired(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    [$start, $end] = $this->eventTimestamps();

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
          'title' => 'Event Without Primary Org',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => '',
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['PRIMARY_ORGANIZATION_REQUIRED'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Without Primary Org'), 'No event when primary organization is empty.');
  }

  /**
   * Test successfully creating an event in an organization.
   */
  public function testCreateEventInOrganization(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $organization = $this->createOrganization('Public Organization', 'public');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$start, $end] = $this->eventTimestamps();
    $title = 'Event in Organization';

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { title }
          }
        }
        GQL,
      [
        'input' => [
          'title' => $title,
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => [
            'title' => $title,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . ($max_nid_before + 1)])
        ->addCacheContexts(['languages:language_interface'])
    );

    $organization_id = $organization->id();
    assert($organization_id !== NULL);
    $this->assertEventInOrganization($max_nid_before + 1, $organization_id);
  }

  /**
   * Test creating an event with cross-posted organizations.
   */
  public function testCreateEventWithCrosspostedOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $primary_org = $this->createOrganization('Primary Organization', 'public');
    $cross_org_1 = $this->createOrganization('Cross Organization 1', 'public');
    $cross_org_2 = $this->createOrganization('Cross Organization 2', 'public');

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$start, $end] = $this->eventTimestamps();
    $title = 'Event Cross-posted to Organizations';

    $this->assertResults(
      <<<GQL
        mutation CreateEvent(\$input: CreateEventInput!) {
          createEvent(input: \$input) {
            errors
            event { title }
          }
        }
        GQL,
      [
        'input' => [
          'title' => $title,
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $primary_org->uuid(),
            'crosspostedOrganizations' => [
              $cross_org_1->uuid(),
              $cross_org_2->uuid(),
            ],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => NULL,
          'event' => ['title' => $title],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . ($max_nid_before + 1)])
        ->addCacheContexts(['languages:language_interface'])
    );

    $event_id = $max_nid_before + 1;
    $primary_org_id = $primary_org->id();
    $cross_org_1_id = $cross_org_1->id();
    $cross_org_2_id = $cross_org_2->id();
    assert($primary_org_id !== NULL && $cross_org_1_id !== NULL && $cross_org_2_id !== NULL);
    $this->assertEventInOrganization($event_id, $primary_org_id);
    $this->assertEventInOrganization($event_id, $cross_org_1_id);
    $this->assertEventInOrganization($event_id, $cross_org_2_id);
  }

  /**
   * Test validation when primary organization is duplicated in crossposted.
   */
  public function testCreateEventWithDuplicateOrganization(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    [$start, $end] = $this->eventTimestamps();

    $organization = $this->createOrganization('Test Organization', 'public');

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
          'title' => 'Event Duplicate Org',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [$organization->uuid()],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['ORGANIZATION_DUPLICATE'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Duplicate Org'), 'No event when primary organization is duplicated in crossposted.');
  }

  /**
   * Test validation error when too many cross-posted organizations.
   */
  public function testCreateEventTooManyCrossPostedOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    [$start, $end] = $this->eventTimestamps();

    $primary_org = $this->createOrganization('Primary Organization', 'public');

    $crossposted_organizations = [];
    for ($i = 0; $i < 51; $i++) {
      $org = $this->createOrganization('Cross-posted Organization ' . $i, 'public');
      $crossposted_organizations[] = $org->uuid();
    }

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
          'title' => 'Event Too Many Orgs',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $primary_org->uuid(),
            'crosspostedOrganizations' => $crossposted_organizations,
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['LIMIT_EXCEEDED_FOR_CROSSPOSTED_ORGANIZATIONS'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Too Many Orgs'), 'No event when cross-posted organizations exceed limit.');
  }

  /**
   * Test validation error when cross-posted organization doesn't exist.
   */
  public function testCreateEventWithInvalidCrossPostedOrganization(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    [$start, $end] = $this->eventTimestamps();

    $organization = $this->createOrganization('Test Organization', 'public');
    $fake_org_uuid = '12345678-1234-1234-1234-123456789012';

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
          'title' => 'Event Invalid Crossposted Org',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [$fake_org_uuid],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['CROSSPOSTED_ORGANIZATION_NOT_FOUND:' . $fake_org_uuid],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event Invalid Crossposted Org'), 'No event when cross-posted organization is not found.');
  }

  /**
   * Test that creating an event in a members-only org without membership fails.
   */
  public function testCreateEventInMembersOrganizationWithoutMembershipFails(): void {
    // Skip test if social_organization is not in the codebase.
    if (!$this->organizationAvailable) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    // Create members-only org but do NOT add the current user as member.
    $group = Group::create([
      'type' => 'organization',
      'label' => 'Members Only Organization',
      'uid' => 1,
      'field_flexible_group_visibility' => 'members',
      'status' => 1,
    ]);
    assert($group->bundle() === 'organization');
    $group->save();

    [$start, $end] = $this->eventTimestamps();

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
          'title' => 'Event in Members Org',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => $group->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createEvent' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
    $this->assertSame(0, $this->getEventCountByTitle('Event in Members Org'), 'No event when actor cannot view members-only organization.');
  }

}
