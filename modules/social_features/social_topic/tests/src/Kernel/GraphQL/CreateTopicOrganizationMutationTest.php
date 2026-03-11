<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\social_group\Entity\Group;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_topic\Kernel\SocialTopicGraphQLKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Test coverage for createTopic GraphQL mutation organization features.
 *
 * Covers creating topics in organizations, cross-posting to organizations,
 * and validation errors for organization input.
 *
 * All tests are skipped when social_organization is not in the codebase.
 *
 * @group social_topic
 */
class CreateTopicOrganizationMutationTest extends SocialTopicGraphQLKernelTestBase {

  /**
   * Max cross-posted organizations.
   *
   * This matches the value in SocialOrganizationInputValidationService.
   */
  private const MAX_CROSSPOSTED_ORGANIZATIONS = 50;

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
   * Data provider for topic visibility × organization visibility combinations.
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
   * Test: Unauthenticated request with organizations fails.
   *
   * Returns error and creates no topic.
   */
  public function testCreateTopicInOrganizationFailsWhenNotAuthenticated(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $publicOrganization = $this->createOrganization('Public Organization', 'public');

    $this->setCurrentUser(User::getAnonymousUser());

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $countBefore = (int) $nodeStorage->getQuery()->count()->accessCheck(FALSE)->execute();

    $this->assertErrors(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic in Organization',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $publicOrganization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      ['/scope|authorization|authenticated/i'],
      $this->defaultMutationCacheMetaData()
    );

    $countAfter = (int) $nodeStorage->getQuery()->count()->accessCheck(FALSE)->execute();
    $this->assertSame($countBefore, $countAfter, 'No new topic should be created when unauthenticated.');
  }

  /**
   * Test: Successfully create a topic with given visibility in organization.
   *
   * @dataProvider topicVisibilityInOrganizationProvider
   */
  public function testCreateTopicInOrganization(string $topicVisibility, string $organizationVisibility, string $title): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }
    if ($organizationVisibility === 'members') {
      $this->markTestSkipped('Members organization visibility option is not supported in this test.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization("{$organizationVisibility} Organization", $organizationVisibility);

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $maxNidBefore = $this->getMaxNodeId($nodeStorage);

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { title visibility }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => $title,
          'visibility' => $topicVisibility,
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => [
            'title' => $title,
            'visibility' => $topicVisibility,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()->addCacheTags(['node:' . ($maxNidBefore + 1)])
    );

    $organizationId = $organization->id();
    assert($organizationId !== NULL);
    $this->assertTopicInOrganization($maxNidBefore + 1, $organizationId);
  }

  /**
   * Test: Create topic with cross-posting to multiple organizations.
   */
  public function testCreateTopicWithCrosspostedOrganizations(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Cross-posted']);
    $topicType->save();

    $primaryOrganization = $this->createOrganization('Primary Organization', 'public');
    $crossOrganization1 = $this->createOrganization('Cross Organization 1', 'public');
    $crossOrganization2 = $this->createOrganization('Cross Organization 2', 'public');

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $maxNidBefore = $this->getMaxNodeId($nodeStorage);

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { title }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Cross-posted Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $primaryOrganization->uuid(),
            'crosspostedOrganizations' => [$crossOrganization1->uuid(), $crossOrganization2->uuid()],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => ['title' => 'Cross-posted Topic'],
        ],
      ],
      $this->defaultMutationCacheMetaData()->addCacheTags(['node:' . ($maxNidBefore + 1)])
    );

    $topic = $nodeStorage->load($maxNidBefore + 1);
    assert($topic instanceof NodeInterface);

    $organizationRefs = $topic->get('organizations_group')->getValue();
    $gids = array_map('strval', array_column($organizationRefs, 'target_id'));
    $expected = [
      (string) $primaryOrganization->id(),
      (string) $crossOrganization1->id(),
      (string) $crossOrganization2->id(),
    ];
    $this->assertEquals($expected, $gids, 'Organizations should be stored with primary first, then cross-posted.');
  }

  /**
   * Test: Members-only organization when user is not a member returns error.
   *
   * User without "manage all groups" cannot create topic in a members-only
   * organization they are not a member of. OrganizationInputValidationService
   * uses access('view') which fails for non-members.
   */
  public function testCreateTopicWithMembersOrganizationWhenNotMemberReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $membersOrg = Group::create([
      'type' => 'organization',
      'label' => 'Members Only Organization',
      'uid' => 1,
      'field_flexible_group_visibility' => 'members',
      'status' => 1,
    ]);
    assert($membersOrg->bundle() === 'organization');
    // Do NOT add the current user as a member.
    $membersOrg->save();

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $membersOrg->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Invalid organization UUID returns validation error.
   */
  public function testCreateTopicWithInvalidOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $fakeUuid = '00000000-0000-0000-0000-000000000000';

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $fakeUuid,
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Topic not in allowed content types returns error.
   *
   * Topic not in allowed content types returns
   * CONTENT_TYPE_NOT_ALLOWED_FOR_ORGANIZATIONS.
   */
  public function testCreateTopicWithTopicNotInAllowedContentTypesReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    // Remove node:topic from allowed types (default has it).
    $this->config('social_organization.settings')
      ->set('types', ['node:event' => 'node:event'])
      ->save();

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Test Organization', 'public');

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CONTENT_TYPE_NOT_ALLOWED_FOR_ORGANIZATIONS:topic'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Unpublished organization returns ORGANIZATION_NOT_FOUND.
   *
   * Scope organization:read grants access to published organizations only;
   * unpublished organizations fail access('view') and thus loadOrganization
   * returns NULL.
   */
  public function testCreateTopicWithUnpublishedOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $unpublishedOrg = Group::create([
      'type' => 'organization',
      'label' => 'Unpublished Organization',
      'uid' => 1,
      'field_flexible_group_visibility' => 'public',
      'status' => 0,
    ]);
    $unpublishedOrg->save();

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $unpublishedOrg->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: User without organization:read gets error when using organizations.
   *
   * Without organization:read, the token lacks view organization permissions;
   * loadOrganization fails access and returns NULL, with error returning
   * ORGANIZATION_NOT_FOUND.
   */
  public function testCreateTopicWithOrganizationsWithoutOrganizationReadScopeReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Test Organization', 'public');

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['ORGANIZATION_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Empty primary organization returns PRIMARY_ORGANIZATION_REQUIRED.
   */
  public function testCreateTopicWithEmptyPrimaryOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => '',
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['PRIMARY_ORGANIZATION_REQUIRED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Duplicate organization returns error.
   *
   * Duplicate between primary and crossposted, or duplicate within
   * crossposted list only, returns ORGANIZATION_DUPLICATE.
   */
  public function testCreateTopicWithDuplicateOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $organization = $this->createOrganization('Test Organization', 'public');

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $organization->uuid(),
            'crosspostedOrganizations' => [$organization->uuid()],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['ORGANIZATION_DUPLICATE'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Too many crossposted organizations returns error.
   *
   * Too many crossposted organizations returns
   * LIMIT_EXCEEDED_FOR_CROSSPOSTED_ORGANIZATIONS.
   */
  public function testCreateTopicWithTooManyCrosspostedOrganizationsReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $primaryOrganization = $this->createOrganization('Primary Organization', 'public');
    $crosspostedUuids = [];
    // Exceed MAX_CROSSPOSTED_ORGANIZATIONS.
    for ($i = 0; $i < self::MAX_CROSSPOSTED_ORGANIZATIONS + 1; $i++) {
      $organization = $this->createOrganization("Cross Organization {$i}", 'public');
      $crosspostedUuids[] = $organization->uuid();
    }

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $primaryOrganization->uuid(),
            'crosspostedOrganizations' => $crosspostedUuids,
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['LIMIT_EXCEEDED_FOR_CROSSPOSTED_ORGANIZATIONS'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Invalid UUID in crossposted organizations returns error.
   *
   * Invalid UUID in crossposted organizations returns
   * CROSSPOSTED_ORGANIZATION_NOT_FOUND.
   */
  public function testCreateTopicWithInvalidCrosspostedOrganizationReturnsError(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $primaryOrganization = $this->createOrganization('Primary Organization', 'public');
    $fakeUuid = '00000000-0000-0000-0000-000000000001';

    $this->actAsClientCredentialsWithScopes(['topic:write', 'organization:read']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => $primaryOrganization->uuid(),
            'crosspostedOrganizations' => [$fakeUuid],
          ],
        ],
      ],
      [
        'createTopic' => [
          'errors' => ['CROSSPOSTED_ORGANIZATION_NOT_FOUND:' . $fakeUuid],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

  /**
   * Test: Topic without organizations input works (backward compatible).
   */
  public function testCreateTopicWithoutOrganizationsStillWorks(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not available.');
    }

    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Standalone']);
    $topicType->save();

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $maxNidBefore = $this->getMaxNodeId($nodeStorage);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { title }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic Without Organization',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
        ],
      ],
      [
        'createTopic' => [
          'errors' => NULL,
          'topic' => ['title' => 'Topic Without Organization'],
        ],
      ],
      $this->defaultMutationCacheMetaData()->addCacheTags(['node:' . ($maxNidBefore + 1)])
    );

    $topic = $nodeStorage->load($maxNidBefore + 1);
    assert($topic instanceof NodeInterface);
    $this->assertTrue(
      $topic->get('organizations_group')->isEmpty(),
      'Topic without organizations input should have no organization reference.'
    );
  }

}
