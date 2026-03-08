<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel;

use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\Entity\Group;

/**
 * Base class for social_event GraphQL kernel tests.
 *
 * Provides shared module list and bootstrap (entity schemas, config, OAuth,
 * current user) for event GraphQL mutation/query tests. Does not include
 * social_organization so tests can run when that module is unavailable.
 * Subclasses may extend this and call parent::setUp() then optionally enable
 * social_organization for organization-related tests.
 *
 * @group social_event
 */
abstract class SocialEventGraphQLKernelTestBase extends SocialGraphQLTestBase {

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
   * Core modules required for event GraphQL tests. Subclasses may override
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
   * Returns the module list for event GraphQL tests.
   *
   * Subclasses may merge this with optional modules (e.g. social_organization)
   * so the kernel boots with them and config schema is built correctly.
   *
   * @return string[]
   *   Module machine names.
   */
  public static function getModules(): array {
    return static::$modules;
  }

  /**
   * Returns the list of config modules to install in setUpCommonSchemaConfig().
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
      'social_editor',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCommonSchemaConfig();
  }

  /**
   * Performs common event GraphQL test bootstrap.
   *
   * Installs entity schemas, config (without social_organization), OAuth
   * settings, keys, and current user. Subclasses may override and call
   * parent::setUpCommonSchemaConfig() before or after adding test-specific
   * setup.
   */
  protected function setUpCommonSchemaConfig(): void {
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

    $this->config('social_group.settings')
      ->set('cross_posting.status', TRUE)
      ->set('cross_posting.content_types', ['event'])
      ->set('cross_posting.group_types', ['flexible_group'])
      ->save();
  }

  /**
   * Checks if the social_organization module exists in the codebase.
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
   * Installs view config required for organization (group) entity save.
   *
   * Organization save and social_organization_group_insert need views
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
   *
   * @return \Drupal\social_group\Entity\Group
   *   The created organization (group of type organization).
   */
  protected function createOrganization(string $label, string $visibility, int $uid = 1): Group {
    $group = Group::create([
      'type' => 'organization',
      'label' => $label,
      'uid' => $uid,
      'field_flexible_group_visibility' => $visibility,
      'status' => 1,
    ]);
    assert($group->bundle() === 'organization');
    $group->save();

    if ($visibility === 'members') {
      $actor = $this->container->get('current_user')->getAccount();
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
   * Asserts that the event node is assigned to the given organization.
   *
   * @param int|string $nodeId
   *   The event node ID.
   * @param int|string $orgId
   *   The organization (group) ID.
   */
  protected function assertEventInOrganization(int|string $nodeId, int|string $orgId): void {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($nodeId);
    assert($node instanceof NodeInterface);
    $refs = $node->get('organizations_group')->getValue();
    $gids = array_map('strval', array_column($refs, 'target_id'));
    $this->assertContains((string) $orgId, $gids, "Event {$nodeId} should be in organization {$orgId}.");
  }

  /**
   * Returns minimal event timestamps for group tests.
   *
   * @return array{0: int, 1: int}
   *   [startTimestamp, endTimestamp].
   */
  protected static function eventTimestamps(): array {
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
   * @return \Drupal\social_group\Entity\Group
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
  protected function getEventCountByTitle(string $title): int {
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
  protected function getEventByTitle(string $title): ?NodeInterface {
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
   * Minimal valid Rich Text JSON body (paragraph with text).
   */
  protected function minimalRichTextBody(): array {
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

}
