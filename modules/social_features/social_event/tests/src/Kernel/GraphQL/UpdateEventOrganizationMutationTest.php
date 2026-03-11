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
 * Test coverage for updateEvent GraphQL mutation organization features.
 *
 * Covers updating event organization associations, cross-posting, and
 * validation errors for organization input.
 *
 * All tests are skipped when social_organization is not in the codebase.
 *
 * @group social_event
 */
class UpdateEventOrganizationMutationTest extends SocialEventGraphQLKernelTestBase {

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
   * Organizations omitted from input leaves event's organizations unchanged.
   */
  public function testUpdateEventOrganizationsOmittedUnchanged(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $orgId = $organization->id();
    assert($orgId !== NULL);

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $orgId]],
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
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

    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Organizations null clears organization associations.
   */
  public function testUpdateEventOrganizationsNullCleared(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $orgId = $organization->id();
    assert($orgId !== NULL);

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $orgId]],
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
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

    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventNotInAnyOrganization($eventId);
  }

  /**
   * Organizations with valid value sets event to that organization.
   */
  public function testUpdateEventOrganizationsSet(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $event = $this->createEvent($eventType);

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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

    $eventId = $event->id();
    $orgId = $organization->id();
    assert($eventId !== NULL && $orgId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Change event from one organization to another.
   */
  public function testUpdateEventOrganizationsChange(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $org1 = $this->createOrganization('Organization 1', 'public');
    $org2 = $this->createOrganization('Organization 2', 'public');

    $event = $this->createEvent($eventType, [
      'organizations_group' => [['target_id' => $org1->id()]],
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $primaryOrg = $this->createOrganization('Primary Organization', 'public');
    $crossOrg1 = $this->createOrganization('Cross Organization 1', 'public');
    $crossOrg2 = $this->createOrganization('Cross Organization 2', 'public');

    $event = $this->createEvent($eventType);

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $event = $this->createEvent($eventType);
    $fake_uuid = '00000000-0000-0000-0000-000000000000';

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $eventType = $this->createEventType();
    $organization = $this->createOrganization('Public Organization', 'public');
    $event = $this->createEvent($eventType);

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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

    $eventId = $event->id();
    $orgId = $organization->id();
    assert($eventId !== NULL && $orgId !== NULL);
    $this->assertEventInOrganization($eventId, $orgId);
  }

  /**
   * Updating to a members-only organization without access is rejected.
   */
  public function testUpdateEventOrganizationsAccessRespected(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

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

    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);
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

    $eventId = $event->id();
    assert($eventId !== NULL);
    $this->assertEventNotInAnyOrganization($eventId);
  }

  /**
   * Test updating to GROUP_MEMBER with organizations only (no groups).
   */
  public function testUpdateEventToGroupMemberVisibilityWithOrganizations(): void {
    if (!static::socialOrganizationExists()) {
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
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $event->uuid(),
          'visibility' => 'GROUP_MEMBER',
          'organizations' => [
            'organization' => $organization->uuid(),
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

    $reloadedEvent = $this->reloadEvent($event);
    $this->assertEquals('group', $reloadedEvent->get('field_content_visibility')->value);
    $event_id = $reloadedEvent->id();
    $organization_id = $organization->id();
    assert($event_id !== NULL);
    assert($organization_id !== NULL);
    $this->assertEventInOrganization($event_id, $organization_id);
  }

  /**
   * Clearing groups succeeds when the event already has organizations.
   */
  public function testUpdateEventClearGroupsWithOrganizationRemaining(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $eventType = $this->createEventType();
    $group = $this->createTestGroup('Test Group', ['public', 'group']);
    $organization = $this->createOrganization('Public Organization', 'public');

    $event = $this->createEvent($eventType, [
      'field_content_visibility' => 'group',
      'organizations_group' => [['target_id' => $organization->id()]],
    ]);
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

    $reloadedEvent = $this->reloadEvent($event);
    $this->assertEquals('group', $reloadedEvent->get('field_content_visibility')->value);
    $relationships = GroupRelationship::loadByEntity($reloadedEvent);
    $groupIds = array_map(fn ($rel) => $rel->getGroupId(), $relationships);
    $this->assertNotContains($group->id(), $groupIds, 'Event should no longer be linked to the group after clearing groups.');
    $event_id = $reloadedEvent->id();
    $organization_id = $organization->id();
    assert($event_id !== NULL);
    assert($organization_id !== NULL);
    $this->assertEventInOrganization($event_id, $organization_id);
  }

  /**
   * Removing groups succeeds when organizations are provided in the same.
   */
  public function testUpdateEventRemoveGroupsWithOrganizationsProvidedInSameMutation(): void {
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write', 'organization:read']);

    $eventType = $this->createEventType();
    $group = $this->createTestGroup('Test Group', ['public', 'group']);
    $organization = $this->createOrganization('Public Organization', 'public');

    $event = $this->createEvent($eventType, [
      'field_content_visibility' => 'group',
    ]);
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
          'organizations' => [
            'organization' => $organization->uuid(),
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

    $reloadedEvent = $this->reloadEvent($event);
    $this->assertEquals('group', $reloadedEvent->get('field_content_visibility')->value);
    $relationships = GroupRelationship::loadByEntity($reloadedEvent);
    $groupIds = array_map(fn ($rel) => $rel->getGroupId(), $relationships);
    $this->assertNotContains($group->id(), $groupIds, 'Event should no longer be linked to the group.');
    $event_id = $reloadedEvent->id();
    $organization_id = $organization->id();
    assert($event_id !== NULL);
    assert($organization_id !== NULL);
    $this->assertEventInOrganization($event_id, $organization_id);
  }

}
