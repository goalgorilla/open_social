<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL\Mutation\Create;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for createEvent GraphQL mutation organization features.
 *
 * Covers creating events in organizations, cross-posting to organizations,
 * and validation errors for organization input.
 *
 * All tests are skipped when social_organization is not in the codebase.
 *
 * @group social_event
 */
class CreateEventOrganizationMutationTest extends SocialEventGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   *
   * Include social_organization and social_tagging so the kernel can boot with
   * them when present. social_organization config requires the tag service.
   * Removed in setUpBeforeClass() when the module is not in the codebase.
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
    if ($this->container->get('module_handler')->moduleExists('social_tagging')) {
      $config[] = 'social_tagging';
    }
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

    if ($this->container->get('module_handler')->moduleExists('social_organization')) {
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
   * Test validation error when organization UUID is not found.
   */
  public function testCreateEventOrganizationNotFound(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    [$start, $end] = $this->eventTimestamps();

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    [$start, $end] = $this->eventTimestamps();

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $organization = $this->createOrganization('Public Organization', 'public');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$start, $end] = $this->eventTimestamps();
    $title = 'Event in Organization';

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $primary_org = $this->createOrganization('Primary Organization', 'public');
    $cross_org_1 = $this->createOrganization('Cross Organization 1', 'public');
    $cross_org_2 = $this->createOrganization('Cross Organization 2', 'public');

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $max_nid_before = $this->getMaxNodeId($node_storage);

    [$start, $end] = $this->eventTimestamps();
    $title = 'Event Cross-posted to Organizations';

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    [$start, $end] = $this->eventTimestamps();

    $organization = $this->createOrganization('Test Organization', 'public');

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    [$start, $end] = $this->eventTimestamps();

    $primary_org = $this->createOrganization('Primary Organization', 'public');

    $crossposted_organizations = [];
    for ($i = 0; $i < 51; $i++) {
      $org = $this->createOrganization('Cross-posted Organization ' . $i, 'public');
      $crossposted_organizations[] = $org->uuid();
    }

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    [$start, $end] = $this->eventTimestamps();

    $organization = $this->createOrganization('Test Organization', 'public');
    $fake_org_uuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

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

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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

  /**
   * Test that GROUP_MEMBER is rejected when only organizations are provided.
   */
  public function testCreateEventGroupMemberVisibilityWithOrganizations(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    [$start, $end] = $this->eventTimestamps();
    $organization = $this->createOrganization('Test Organization', 'public');

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
          'type' => $eventType->uuid(),
          'title' => 'Event GROUP_MEMBER with organizations',
          'visibility' => 'GROUP_MEMBER',
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
          'errors' => ['GROUP_REQUIRED_FOR_GROUP_VISIBILITY'],
          'event' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $this->assertSame(
      0,
      $this->getEventCountByTitle('Event GROUP_MEMBER with organizations'),
      'No event should be created for GROUP_MEMBER without a flexible group.'
    );
  }

  /**
   * Test that GROUP_MEMBER succeeds with a flexible group and an organization.
   */
  public function testCreateEventGroupMemberVisibilityWithGroupAndOrganization(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    [$start, $end] = $this->eventTimestamps();
    $group = $this->createTestGroup('Test Group', ['public', 'group']);
    $organization = $this->createOrganization('Test Organization', 'public');

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
          'type' => $eventType->uuid(),
          'title' => 'Event GROUP_MEMBER with group and organization',
          'visibility' => 'GROUP_MEMBER',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [],
          ],
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
            'title' => 'Event GROUP_MEMBER with group and organization',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:1'])
    );

    $node = $this->getEventByTitle('Event GROUP_MEMBER with group and organization');
    $this->assertNotNull($node);
    $this->assertSame('group', $node->get('field_content_visibility')->value);
    $relationships = GroupRelationship::loadByEntity($node);
    $groupIds = array_map(fn ($rel) => $rel->getGroupId(), $relationships);
    $this->assertContains($group->id(), $groupIds, 'Event should be linked to the flexible group.');
    $eventId = $node->id();
    $organizationId = $organization->id();
    assert($eventId !== NULL);
    assert($organizationId !== NULL);
    $this->assertEventInOrganization($eventId, $organizationId);
  }

}
