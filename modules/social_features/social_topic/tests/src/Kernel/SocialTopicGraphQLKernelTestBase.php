<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\social_group\Entity\Group;
use Drupal\node\NodeInterface;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base class for social_topic GraphQL kernel tests.
 *
 * Provides shared module list and bootstrap (entity schemas, config, OAuth,
 * current user) for topic GraphQL mutation/query tests. Subclasses extend this
 * and call parent::setUp() so only test-specific bootstrapping remains.
 *
 * @group social_topic
 */
abstract class SocialTopicGraphQLKernelTestBase extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   *
   * Core modules required for topic GraphQL tests. Subclasses may override
   * to add modules (e.g. social_organization) or use this as-is for tests
   * without organization support.
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
  protected function setUp(): void {
    parent::setUp();

    $this->setUpTopicGraphQlCommon();
  }

  /**
   * Performs common topic GraphQL test bootstrap.
   *
   * Installs entity schemas, config, OAuth settings, keys, and current user.
   * Subclasses may override this and call parent::setUpTopicGraphQLCommon()
   * before or after adding test-specific setup.
   */
  protected function setUpTopicGraphQlCommon(): void {
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

    $this->installConfig($this->getConfigToInstall());

    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    $this->setUpCurrentUser(
      ['uid' => 1],
      [],
      FALSE
    );
  }

  /**
   * Returns the list of config modules to install in setUpTopicGraphQlCommon().
   *
   * Subclasses may override to add modules (e.g. social_organization) when
   * they are present in $modules.
   *
   * @return string[]
   *   Config module names to install.
   */
  protected function getConfigToInstall(): array {
    return [
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
      'flag',
      'simple_oauth',
      'simple_oauth_static_scope',
    ];
  }

  /**
   * Checks if the social_organization module exists in the codebase.
   *
   * @return bool
   *   TRUE if the module info file exists.
   */
  protected static function socialOrganizationExists(): bool {
    if (!defined('DRUPAL_ROOT')) {
      return FALSE;
    }
    $paths = [
      DRUPAL_ROOT . '/modules/extensions/social_organization/social_organization.info.yml',
      DRUPAL_ROOT . '/modules/contrib/social_organization/social_organization.info.yml',
    ];
    foreach ($paths as $path) {
      if (file_exists($path)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Installs view config required for Organization entity save.
   *
   * Organization::save() and social_organization_group_insert need views
   * (group_members, upcoming_events, newest_groups, latest_topics).
   */
  protected function installOrganizationRequiredViews(): void {
    $config_installer = $this->container->get('config.installer');
    $path_resolver = $this->container->get('extension.path.resolver');

    $config_dirs = [
      $path_resolver->getPath('module', 'social_group') . '/config/optional',
      $path_resolver->getPath('module', 'social_event') . '/config/install',
    ];

    foreach ($config_dirs as $dir) {
      if (is_dir($dir)) {
        $storage = new FileStorage($dir, StorageInterface::DEFAULT_COLLECTION);
        $config_installer->installOptionalConfig($storage);
      }
    }

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Creates an organization (group of type organization).
   *
   * For "members" visibility, the current user (OAuth actor) must be a member
   * to view the organization; otherwise OrganizationInputValidationService
   * returns ORGANIZATION_NOT_FOUND. So we add the current user as a member.
   *
   * @param string $label
   *   The organization label.
   * @param string $visibility
   *   The visibility (e.g. 'public', 'members').
   * @param int $uid
   *   The owner user ID.
   * @param bool $addCurrentUserAsMember
   *   When TRUE and visibility is 'members', add the current user as a member.
   *   When FALSE, do not add (for testing non-member access denial).
   *
   * @return \Drupal\social_group\Entity\Group
   *   The created organization (group of type organization).
   */
  protected function createOrganization(string $label, string $visibility, int $uid = 1, bool $addCurrentUserAsMember = TRUE): Group {
    $group = Group::create([
      'type' => 'organization',
      'label' => $label,
      'uid' => $uid,
      'field_flexible_group_visibility' => $visibility,
      'status' => 1,
    ]);
    $group->save();

    if ($visibility === 'members' && $addCurrentUserAsMember) {
      $actor = $this->container->get('current_user')->getAccount();
      /** @var \Drupal\social_group\Entity\Group $group */
      if (!$group->hasMember($actor)) {
        $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
        $user = $user_storage->load($actor->id());
        if ($user !== NULL) {
          $group->addMember($user);
        }
      }
    }

    return $group;
  }

  /**
   * Returns the maximum node ID in storage (0 if no nodes exist).
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $nodeStorage
   *   The node storage.
   *
   * @return int
   *   The maximum node ID.
   */
  protected function getMaxNodeId(EntityStorageInterface $nodeStorage): int {
    /** @phpstan-ignore method.alreadyNarrowedType */
    $ids = $nodeStorage->getQuery()->accessCheck(FALSE)->execute();
    return empty($ids) ? 0 : (int) max($ids);
  }

  /**
   * Asserts that the topic node is assigned to the given organization.
   *
   * Uses the organization reference field name so the base does not depend
   * on the Organization class.
   *
   * @param int|string $nodeId
   *   The topic node ID.
   * @param int|string $orgId
   *   The organization (group) ID.
   */
  protected function assertTopicInOrganization(int|string $nodeId, int|string $orgId): void {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load((int) $nodeId);
    assert($node instanceof NodeInterface);
    $refs = $node->get('organizations_group')->getValue();
    $gids = array_map('strval', array_column($refs, 'target_id'));
    $this->assertContains((string) $orgId, $gids, "Topic {$nodeId} should be in organization {$orgId}.");
  }

  /**
   * Asserts that the topic node has no organization assignments.
   *
   * @param int|string $nodeId
   *   The topic node ID.
   */
  protected function assertTopicNotInAnyOrganization(int|string $nodeId): void {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load((int) $nodeId);
    assert($node instanceof NodeInterface);
    $this->assertTrue(
      $node->get('organizations_group')->isEmpty(),
      "Topic {$nodeId} should not be in any organization."
    );
  }

}
