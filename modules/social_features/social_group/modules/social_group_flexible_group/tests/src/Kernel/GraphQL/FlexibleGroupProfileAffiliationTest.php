<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Kernel\GraphQL;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\graphql_oauth\Entity\Server;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\Entity\Bundle\SocialProfile;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile_privacy\Service\SocialProfilePrivacyHelperInterface;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests profile affiliation GraphQL fields with flexible-group hubs.
 *
 * @group social_graphql
 */
class FlexibleGroupProfileAffiliationTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testUser;

  /**
   * Test viewer user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testViewer;

  /**
   * Test user profile.
   *
   * @var \Drupal\social_profile\Entity\Bundle\SocialProfile
   */
  protected SocialProfile $testProfile;

  /**
   * First group - hidden other.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $firstGroup;

  /**
   * Second group - visible other.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $secondGroup;

  /**
   * Third group - hidden other.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $thirdGroup;

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
    'social_group',
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    'social_group_flexible_group',
    'social_group_request',
    'social_media_system',
    'select2',
    'text',
    'taxonomy',
    'taxonomy_access_fix',
    'pathauto',
    'smart_trim',
    'path',
    'path_alias',
    'token',

    'diff',
    'inline_entity_form',
    'workflows',
    'content_moderation',

    // Required by social_group.
    'better_exposed_filters',
    'filter',
    'views_bulk_operations',
    'flag',
    'gnode',
    'social_event',
    'social_event_type',
    'social_topic',
    'social_tagging',

    // Required by social_event.
    'profile',
    'social_profile',
    'social_profile_privacy',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'social_node',
    'social_core',

    // Meeting API modules required by social_event configurations.
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',

    // Required by social_core.
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

    // Required by social_profile.
    'telephone',
    // Required by medium_teaser view mode.
    'lazy',
    'serialization',
    'group',
    'file',
    'image',
    'options',
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
    'filter',
    'menu_link_content',
    'flag',
    'field',
    'social_group_invite',
    'ginvite',
    'entity_browser',

    // Required for our body field.
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('file');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('pathauto_pattern');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('user');
    $this->installEntitySchema('flag');
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    // Required for social_group_request.
    $this->installEntitySchema('activity');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('comment', ['comment_entity_statistics']);

    $this->installConfig([
      'node',
      'user',
      'profile',
      'menu_link_content',
      'social_profile',
      'social_profile_privacy',
      'social_node',
      'social_core',
      'group',
      'grequest',
      'state_machine',
      'social_event',
      'social_event_type',
      'social_topic',
      'social_tagging',
      'social_group_invite',
      'ginvite',
      'social_group',
      // Required for social_group_request.
      'activity_logger',
      'activity_creator',
      'social_group_flexible_group',
      'social_group_request',
      'social_media_system',
      'file',
      'field',
      'media',
      'taxonomy',
      'text',
      'paragraphs',
      'system',
      'pathauto',
      'smart_trim',
      'subgroup',
      'simple_oauth',
      'simple_oauth_static_scope',
      'flag',
      'social_editor',
    ]);

    $this->config('simple_oauth.settings')
      ->set('scope_provider', 'static')
      ->save();
    $this->setUpKeys();
    $this->assertInstanceOf(Server::class, $this->server);

    $this->setUpCurrentUser(["uid" => 1], [], TRUE);

    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);
  }

  /**
   * Tests visible primary and other affiliations over GraphQL.
   *
   * Test:
   *   Primary affiliation visible.
   *   Other affiliations visible.
   *   Membership id, function, and user from the shared trait.
   */
  public function testFlexibleGroupVisibleProfileAffiliations(): void {
    $this->createAffiliationSetup();
    $this->setCurrentUser($this->testUser);

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => [
            '__typename' => 'UserGroupAffiliation',
            'id' => $this->membershipUuid($this->firstGroup),
            'function' => NULL,
            'user' => [
              'id' => $this->testUser->uuid(),
            ],
            'group' => [
              'id' => $this->firstGroup->uuid(),
              'name' => $this->firstGroup->label(),
            ],
          ],
          'affiliations' => [
            [
              '__typename' => 'UserGroupAffiliation',
              'id' => $this->membershipUuid($this->firstGroup),
              'function' => NULL,
              'user' => [
                'id' => $this->testUser->uuid(),
              ],
              'group' => [
                'id' => $this->firstGroup->uuid(),
                'name' => $this->firstGroup->label(),
              ],
            ],
            [
              '__typename' => 'UserGroupAffiliation',
              'id' => $this->membershipUuid($this->secondGroup),
              'function' => 'Pilot',
              'user' => [
                'id' => $this->testUser->uuid(),
              ],
              'group' => [
                'id' => $this->secondGroup->uuid(),
                'name' => $this->secondGroup->label(),
              ],
            ],
            [
              '__typename' => 'UserGroupAffiliation',
              'id' => $this->membershipUuid($this->thirdGroup),
              'function' => NULL,
              'user' => [
                'id' => $this->testUser->uuid(),
              ],
              'group' => [
                'id' => $this->thirdGroup->uuid(),
                'name' => $this->thirdGroup->label(),
              ],
            ],
            [
              '__typename' => 'ExternalProfileAffiliation',
              'function' => 'Advisor',
              'name' => 'External Org',
            ],
          ],
        ],
      ],
    ];

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $this->createMetadataForProfileAffiliations($this->testUser, [
        $this->firstGroup,
        $this->secondGroup,
        $this->thirdGroup,
      ]),
    );
  }

  /**
   * Tests hidden primary and other affiliations over GraphQL.
   *
   * Test:
   *   A hidden primary hub falls back to the first external affiliation.
   *   A hidden primary not listed in affiliations.
   *   Other hubs omitted when viewer cannot view the hub.
   */
  public function testFlexibleGroupHiddenProfileAffiliations(): void {
    $this->createAffiliationSetup();
    $this->setCurrentUser($this->testViewer);

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => [
            '__typename' => 'ExternalProfileAffiliation',
            'function' => 'Advisor',
            'name' => 'External Org',
          ],
          'affiliations' => [
            [
              '__typename' => 'UserGroupAffiliation',
              'id' => $this->membershipUuid($this->secondGroup),
              'function' => 'Pilot',
              'user' => [
                'id' => $this->testUser->uuid(),
              ],
              'group' => [
                'id' => $this->secondGroup->uuid(),
                'name' => $this->secondGroup->label(),
              ],
            ],
            [
              '__typename' => 'ExternalProfileAffiliation',
              'function' => 'Advisor',
              'name' => 'External Org',
            ],
          ],
        ],
      ],
    ];

    $cache_metadata = $this->createMetadataForProfileAffiliations(
      $this->testUser,
      [$this->secondGroup],
    );
    // GraphQL surfaces denied hub access as max-age 0.
    $cache_metadata->setCacheMaxAge(0);

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $cache_metadata,
    );
  }

  /**
   * The disabled affiliation feature hides profile affiliations.
   *
   * Test:
   *   Feature flag off gives empty/null results.
   */
  public function testFlexibleGroupProfileAffiliationFeatureDisabled(): void {
    $this->createTestUsersAndProfile();
    $this->setCurrentUser($this->testUser);

    $this->config('social_profile.settings')
      ->set('group_affiliation_status', FALSE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => NULL,
          'affiliations' => [],
        ],
      ],
    ];

    $cache_metadata = $this->createMetadataForProfileAffiliations();
    $cache_metadata->addCacheableDependency($this->testUser);
    $cache_metadata->addCacheableDependency($this->testProfile);

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $cache_metadata,
    );
  }

  /**
   * Privacy-hidden affiliation fields hides profile affiliations.
   *
   * Test:
   *   Field-level / privacy access on affiliation fields.
   */
  public function testFlexibleGroupProfileAffiliationFieldPrivacy(): void {
    $this->createTestUsersAndProfile();
    $this->setCurrentUser($this->testViewer);

    $this->config('social_profile_privacy.settings')
      ->set('fields', [
        'field_group_affiliation' => SocialProfilePrivacyHelperInterface::HIDE,
        'field_other_affiliations' => SocialProfilePrivacyHelperInterface::HIDE,
      ])
      ->save(TRUE);
    drupal_static_reset('social_profile_privacy_private_fields_list');

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => NULL,
          'affiliations' => [],
        ],
      ],
    ];

    $cache_metadata = $this->createMetadataForProfileAffiliations();
    $cache_metadata->addCacheableDependency($this->testUser);
    $cache_metadata->addCacheableDependency($this->testProfile);

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $cache_metadata,
    );
  }

  /**
   * Privacy-hidden hub affiliation field still exposes external affiliations.
   *
   * Test:
   *   Hiding field_group_affiliation omits hub memberships but keeps externals.
   */
  public function testFlexibleGroupProfileAffiliationFieldPrivacyHideHubsOnly(): void {
    $this->createAffiliationSetup();
    $this->setCurrentUser($this->testViewer);

    $this->config('social_profile_privacy.settings')
      ->set('fields', [
        'field_group_affiliation' => SocialProfilePrivacyHelperInterface::HIDE,
      ])
      ->save(TRUE);
    drupal_static_reset('social_profile_privacy_private_fields_list');

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => [
            '__typename' => 'ExternalProfileAffiliation',
            'function' => 'Advisor',
            'name' => 'External Org',
          ],
          'affiliations' => [
            [
              '__typename' => 'ExternalProfileAffiliation',
              'function' => 'Advisor',
              'name' => 'External Org',
            ],
          ],
        ],
      ],
    ];

    $cache_metadata = $this->createMetadataForProfileAffiliations();
    $cache_metadata->addCacheableDependency($this->testUser);
    $cache_metadata->addCacheableDependency($this->testProfile);
    $cache_metadata->addCacheTags([
      'config:paragraphs.settings',
      'config:profile.type.profile',
    ]);
    foreach ($this->testProfile->get('field_other_affiliations')->referencedEntities() as $paragraph) {
      $cache_metadata->addCacheableDependency($paragraph);
    }

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $cache_metadata,
    );
  }

  /**
   * Privacy-hidden external affiliation field still exposes visible hubs.
   *
   * Test:
   *   Hiding field_other_affiliations omits externals but keeps visible hubs.
   */
  public function testFlexibleGroupProfileAffiliationFieldPrivacyHideExternalsOnly(): void {
    $this->createAffiliationSetup();
    $this->setCurrentUser($this->testViewer);

    $this->config('social_profile_privacy.settings')
      ->set('fields', [
        'field_other_affiliations' => SocialProfilePrivacyHelperInterface::HIDE,
      ])
      ->save(TRUE);
    drupal_static_reset('social_profile_privacy_private_fields_list');

    $expected_data = [
      'user' => [
        'profile' => [
          'primaryAffiliation' => NULL,
          'affiliations' => [
            [
              '__typename' => 'UserGroupAffiliation',
              'id' => $this->membershipUuid($this->secondGroup),
              'function' => 'Pilot',
              'user' => [
                'id' => $this->testUser->uuid(),
              ],
              'group' => [
                'id' => $this->secondGroup->uuid(),
                'name' => $this->secondGroup->label(),
              ],
            ],
          ],
        ],
      ],
    ];

    $cache_metadata = $this->createMetadataForProfileAffiliations(
      $this->testUser,
      [$this->secondGroup],
    );
    // External affiliations are privacy-hidden; paragraph producers are unused.
    $cache_metadata->setCacheTags(array_values(array_filter(
      $cache_metadata->getCacheTags(),
      static fn (string $tag): bool => !str_starts_with($tag, 'paragraph:')
        && $tag !== 'config:paragraphs.settings'
        && $tag !== 'config:profile.type.profile',
    )));
    // GraphQL surfaces denied hub access as max-age 0.
    $cache_metadata->setCacheMaxAge(0);

    $this->assertResults(
      $this->getQueryForAffiliations(),
      ['id' => $this->testUser->uuid()],
      $expected_data,
      $cache_metadata,
    );
  }

  /**
   * Creates GraphQL users and an empty owner profile.
   */
  private function createTestUsersAndProfile(): void {
    $testUser = $this->createUser([
      'execute open_social_graphql arbitrary graphql requests',
      'access user profiles',
      'view any profile',
      'view community flexible_group group',
      'view members flexible_group group',
    ]);
    $this->assertNotFalse($testUser);
    $this->testUser = $testUser;

    $testViewer = $this->createUser([
      'execute open_social_graphql arbitrary graphql requests',
      'access user profiles',
      'view any profile',
      'view community flexible_group group',
    ]);
    $this->assertNotFalse($testViewer);
    $this->testViewer = $testViewer;

    $this->testProfile = $this->loadOrCreateOwnerProfile();
    $this->testProfile->save();
  }

  /**
   * Creates a profile with mixed hub visibility and an external affiliation.
   */
  private function createAffiliationSetup(): void {
    $this->createTestUsersAndProfile();

    $this->firstGroup = $this->createFlexibleGroup('First Group - hidden other', 'members', $this->testUser);
    $this->secondGroup = $this->createFlexibleGroup('Second Group - visible other', 'community', $this->testUser);
    $this->thirdGroup = $this->createFlexibleGroup('Third Group - hidden other', 'members', $this->testUser);

    $this->firstGroup->addMember($this->testUser);
    $this->secondGroup->addMember($this->testUser);
    $this->thirdGroup->addMember($this->testUser);

    $second_membership = GroupMembership::loadSingle(
      $this->secondGroup,
      $this->testUser,
    );
    $this->assertInstanceOf(GroupMembership::class, $second_membership);
    $second_membership->set('field_affiliation_function', 'Pilot');
    $second_membership->save();

    $this->testProfile
      ->set('field_group_affiliation', [
        ['target_id' => $this->firstGroup->id()],
        ['target_id' => $this->secondGroup->id()],
        ['target_id' => $this->thirdGroup->id()],
      ])
      ->set('field_enable_other_affiliations', TRUE)
      ->addNonPlatformAffiliation('External Org', 'Advisor')
      ->save();
    $this->assertFalse($this->testProfile->get('field_other_affiliations')->isEmpty());
  }

  /**
   * Loads the owner's profile or creates one.
   */
  private function loadOrCreateOwnerProfile(): SocialProfile {
    $profile_storage = $this->container->get('entity_type.manager')
      ->getStorage('profile');
    $profile = $profile_storage->loadByUser($this->testUser, 'profile');
    if ($profile === NULL) {
      $profile = Profile::create([
        'type' => 'profile',
        'uid' => $this->testUser->id(),
        'status' => 1,
      ]);
    }
    assert($profile instanceof SocialProfile);
    return $profile;
  }

  /**
   * Creates a flexible group with the given visibility.
   */
  private function createFlexibleGroup(string $label, string $visibility, UserInterface $owner): Group {
    $group = Group::create([
      'label' => $label,
      'type' => 'flexible_group',
      'uid' => $owner->id(),
      'status' => TRUE,
      'field_flexible_group_visibility' => $visibility,
    ]);
    $group->save();
    return $group;
  }

  /**
   * Returns the membership UUID for the test user in a hub.
   */
  private function membershipUuid(GroupInterface $group): string {
    $membership = GroupMembership::loadSingle($group, $this->testUser);
    $this->assertInstanceOf(GroupMembership::class, $membership);
    $uuid = $membership->uuid();
    $this->assertIsString($uuid);
    return $uuid;
  }

  /**
   * Cache metadata for profile affiliation GraphQL queries.
   *
   * When $user is set, producers also depend on the profile, all hubs
   * checked for view access, external affiliation paragraphs, and
   * group_content tags for memberships that appear in the response.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   Profile owner, or NULL when affiliations are not resolved.
   * @param \Drupal\group\Entity\GroupInterface[] $visible_membership_groups
   *   Hubs whose memberships are returned (group_content tags).
   */
  private function createMetadataForProfileAffiliations(
    ?UserInterface $user = NULL,
    array $visible_membership_groups = [],
  ): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheTags([
      'profile_list',
      'group_affiliation_options_by_user',
      'config:social_profile.settings',
    ]);

    if ($user) {
      $cache_metadata->addCacheContexts([
        'user',
        'user.group_permissions',
      ]);
      $cache_metadata->addCacheTags([
        'config:paragraphs.settings',
        'config:profile.type.profile',
      ]);
      $cache_metadata->addCacheableDependency($user);
      $cache_metadata->addCacheableDependency($this->testProfile);
      $cache_metadata->addCacheableDependency($this->firstGroup);
      $cache_metadata->addCacheableDependency($this->secondGroup);
      $cache_metadata->addCacheableDependency($this->thirdGroup);
      $paragraphs = $this->testProfile
        ->get('field_other_affiliations')
        ->referencedEntities();
      foreach ($paragraphs as $paragraph) {
        $cache_metadata->addCacheableDependency($paragraph);
      }
      foreach ($visible_membership_groups as $group) {
        $membership = GroupMembership::loadSingle($group, $user);
        if ($membership instanceof GroupMembership) {
          $cache_metadata->addCacheableDependency($membership);
        }
      }
    }

    return $cache_metadata;
  }

  /**
   * Helper method to get query for profile affiliation tests.
   *
   * @return string
   *   GraphQL query.
   */
  private function getQueryForAffiliations(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          profile {
            primaryAffiliation {
              __typename
              function
              ... on UserGroupAffiliation {
                id
                user {
                  id
                }
                group {
                  id
                  name
                }
              }
              ... on ExternalProfileAffiliation {
                name
              }
            }
            affiliations {
              __typename
              function
              ... on UserGroupAffiliation {
                id
                user {
                  id
                }
                group {
                  id
                  name
                }
              }
              ... on ExternalProfileAffiliation {
                name
              }
            }
          }
        }
      }
    ';
  }

}
