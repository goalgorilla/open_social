<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_topic\Kernel\SocialTopicGraphQLKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for updateTopic GraphQL mutation organization features.
 *
 * Covers add, remove, change, and leave-unchanged of topic organization
 * membership via the updateTopic mutation.
 *
 * All tests are skipped when social_organization is not in the codebase.
 *
 * @group social_topic
 */
class UpdateTopicOrganizationMutationTest extends SocialTopicGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   *
   * Include social_organization so the kernel can boot with it when present.
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

    $this->config('social_group.settings')
      ->set('cross_posting.status', TRUE)
      ->set('cross_posting.content_types', ['topic'])
      ->set('cross_posting.group_types', ['flexible_group'])
      ->save();

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
   * Test: Add topic to organization when editing.
   *
   * (topic with no org → set to one).
   */
  public function testUpdateTopicAddToOrganization(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic Without Organization',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $organization = $this->createOrganization('Public Organization', 'public');

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $organization->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic Without Organization',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    $orgId = $organization->id();
    assert($topicId !== NULL && $orgId !== NULL);
    $this->assertTopicInOrganization($topicId, $orgId);
  }

  /**
   * Test: Remove topic from all organizations when editing.
   */
  public function testUpdateTopicRemoveFromAllOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Public Organization', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic In Organization',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'organizations_group' => [['target_id' => $organization->id()]],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => NULL,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => ['id' => $topic->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    assert($topicId !== NULL);
    $this->assertTopicNotInAnyOrganization($topicId);
  }

  /**
   * Test: Change which organization(s) a topic is in when editing.
   */
  public function testUpdateTopicChangeOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $org1 = $this->createOrganization('Organization 1', 'public');
    $org2 = $this->createOrganization('Organization 2', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic In Org 1',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'organizations_group' => [['target_id' => $org1->id()]],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $org2->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => ['id' => $topic->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    $org2Id = $org2->id();
    assert($topicId !== NULL && $org2Id !== NULL);
    $this->assertTopicInOrganization($topicId, $org2Id);
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($topicId);
    assert($node instanceof NodeInterface);
    $gids = array_map('strval', array_column($node->get('organizations_group')->getValue(), 'target_id'));
    $this->assertNotContains((string) $org1->id(), $gids, 'Topic should no longer be in org1.');
  }

  /**
   * Test: Update topic with cross-posting to multiple organizations.
   *
   * Adapted from
   * CreateTopicMutationTest::testCreateTopicWithCrosspostedOrganizations.
   */
  public function testUpdateTopicWithCrosspostedOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Cross-posted']);
    $topicType->save();

    $primaryOrganization = $this->createOrganization('Primary Organization', 'public');
    $crossOrganization1 = $this->createOrganization('Cross Organization 1', 'public');
    $crossOrganization2 = $this->createOrganization('Cross Organization 2', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic To Cross-post',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $primaryOrganization->uuid(),
            'crosspostedOrganizations' => [
              $crossOrganization1->uuid(),
              $crossOrganization2->uuid(),
            ],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic To Cross-post',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    assert($topicId !== NULL);
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($topicId);
    assert($node instanceof NodeInterface);
    $organizationRefs = $node->get('organizations_group')->getValue();
    $gids = array_map('strval', array_column($organizationRefs, 'target_id'));
    $expected = [
      (string) $primaryOrganization->id(),
      (string) $crossOrganization1->id(),
      (string) $crossOrganization2->id(),
    ];
    $this->assertEquals($expected, $gids, 'Organizations should be stored with primary first, then cross-posted.');
  }

  /**
   * Data provider for topic visibility × organization visibility combinations.
   *
   * Mirrors CreateTopicMutationTest::topicVisibilityInOrganizationProvider.
   */
  public static function topicVisibilityInOrganizationProvider(): array {
    $topicVisibilities = ['PUBLIC', 'COMMUNITY', 'GROUP_MEMBER'];
    $organizationVisibilities = ['public', 'community', 'members'];
    $topicLabels = ['PUBLIC' => 'Public', 'COMMUNITY' => 'Community', 'GROUP_MEMBER' => 'Members'];
    $organizationLabels = ['public' => 'Public', 'community' => 'Community', 'members' => 'Members'];

    $datasets = [];
    foreach ($organizationVisibilities as $organizationVisibility) {
      foreach ($topicVisibilities as $topicVisibility) {
        $title = "{$topicLabels[$topicVisibility]} Topic in {$organizationLabels[$organizationVisibility]} Organization";
        $name = "{$topicLabels[$topicVisibility]} topic in {$organizationLabels[$organizationVisibility]} organization";
        $datasets[$name] = [$topicVisibility, $organizationVisibility, $title];
      }
    }
    return $datasets;
  }

  /**
   * Maps GraphQL visibility to Drupal field value.
   */
  private static function topicVisibilityToFieldValue(string $graphqlVisibility): string {
    return match ($graphqlVisibility) {
      'PUBLIC' => 'public',
      'COMMUNITY' => 'community',
      'GROUP_MEMBER' => 'group',
      default => throw new \InvalidArgumentException(
        sprintf(
          'Unexpected GraphQL visibility value: "%s". Expected one of: PUBLIC, COMMUNITY, GROUP_MEMBER.',
          $graphqlVisibility
        )
      ),
    };
  }

  /**
   * Test: Successfully update a topic with given visibility to organization.
   *
   * Mirrors CreateTopicMutationTest::testCreateTopicInOrganization for update.
   *
   * @dataProvider topicVisibilityInOrganizationProvider
   */
  public function testUpdateTopicInOrganization(string $topicVisibility, string $organizationVisibility, string $title): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization("{$organizationVisibility} Organization", $organizationVisibility);

    $topic = Node::create([
      'type' => 'topic',
      'title' => $title,
      'body' => [['value' => ' ']],
      'field_content_visibility' => self::topicVisibilityToFieldValue($topicVisibility),
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $organization->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => $title,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    $orgId = $organization->id();
    assert($topicId !== NULL && $orgId !== NULL);
    $this->assertTopicInOrganization($topicId, $orgId);
  }

  /**
   * Test: Leave organization unchanged when editing other fields.
   */
  public function testUpdateTopicLeaveOrganizationsUnchanged(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Public Organization', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'organizations_group' => [['target_id' => $organization->id()]],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => 'Updated Title Only',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title Only',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    $orgId = $organization->id();
    assert($topicId !== NULL && $orgId !== NULL);
    $this->assertTopicInOrganization($topicId, $orgId);
  }

  /**
   * Test: Assigning to members-only organization when user is not a member.
   *
   * User without "manage all groups" cannot view a members-only org they are
   * not in. OrganizationInputValidationService returns ORGANIZATION_NOT_FOUND.
   */
  public function testUpdateTopicWithMembersOrganizationWhenNotMemberReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $membersOrg = $this->createOrganization('Members Organization', 'members', 1, FALSE);

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $membersOrg->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test: Invalid organization reference returns validation error.
   */
  public function testUpdateTopicWithInvalidOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $fakeUuid = '00000000-0000-0000-0000-000000000000';

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $fakeUuid,
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test: Organization user cannot use returns error.
   *
   * Without organization:read scope, loadOrganization fails and returns
   * ORGANIZATION_NOT_FOUND.
   */
  public function testUpdateTopicWithOrganizationsWithoutOrganizationReadScopeReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Test Organization', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $organization->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test: Update without organizations input leaves organizations unchanged.
   */
  public function testUpdateTopicWithoutOrganizationsInputLeavesOrganizationsUnchanged(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Public Organization', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'organizations_group' => [['target_id' => $organization->id()]],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id title }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => 'Updated Title',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $topicId = $topic->id();
    $orgId = $organization->id();
    assert($topicId !== NULL && $orgId !== NULL);
    $this->assertTopicInOrganization($topicId, $orgId);
  }

  /**
   * Test: After successful organization update, load topic shows updated orgs.
   */
  public function testUpdateTopicOrganizationUpdateReflectedOnReload(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $org1 = $this->createOrganization('Organization A', 'public');
    $org2 = $this->createOrganization('Organization B', 'public');

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'organizations_group' => [['target_id' => $org1->id()]],
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'organizations' => [
            'organization' => $org2->uuid(),
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => ['id' => $topic->uuid()],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $topicId = $topic->id();
    assert($topicId !== NULL);
    $reloaded = $nodeStorage->load($topicId);
    assert($reloaded instanceof NodeInterface);
    $refs = $reloaded->get('organizations_group')->getValue();
    $gids = array_map('strval', array_column($refs, 'target_id'));
    $this->assertContains((string) $org2->id(), $gids);
    $this->assertNotContains((string) $org1->id(), $gids);
  }

}
