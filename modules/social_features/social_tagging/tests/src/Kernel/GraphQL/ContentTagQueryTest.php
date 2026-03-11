<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Test coverage for the contentTag GraphQL query.
 *
 * @group social_tagging
 */
class ContentTagQueryTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'text',
    'link',
    'node',
    'field',
    'filter',
    'user',
    'system',
    'serialization',
    'social_core',
    'entity_access_by_field',
    'social_node',
    'social_tagging',
    'social_search',
    'select2',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'social_graphql',
    'graphql_oauth',
    'variationcache',
    'image',
    'file',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'options',
    'comment',
    'menu_ui',
    'menu_link_content',
    'path',
    'path_alias',
    'token',
    'pathauto',
    'taxonomy_access_fix',
    'social_user',
    'role_delegation',
    // Modules required by social_event configurations.
    'address',
    'comment',
    'datetime',
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
    'entity_access_by_field',
    'social_node',
    'social_comment',
    'social_topic',
    'social_event',
    'social_event_type',
    'group',
    'group_core_comments',
    'views',
    'block',
    'block_content',
    // Required for flexible_group permissions in oauth2_scopes.yml.
    'social_group',
    'social_group_flexible_group',
    'social_organization',
    'social_group_request',
    'grequest',
    'activity_logger',
    'activity_creator',
    'message',
    'flag',
    'flag_count',
    'better_exposed_filters',
    'field_group',
    'gnode',
    'dynamic_entity_reference',
    'state_machine',
    'paragraphs',
    'entity_reference_revisions',
    'telephone',
    'smart_trim',
    'views_bulk_operations',
    'layout_builder',
    'layout_discovery',
    'social_group_invite',
    'ginvite',
    'profile',
    'social_profile',

    // Required for our body field.
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installEntitySchema('meeting_api_meeting');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('activity');
    $this->installEntitySchema('message');
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('flag');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('path_alias');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('layout_builder', ['inline_block_usage']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('flag', ['flag_counts']);

    $this->installConfig([
      'taxonomy',
      'social_tagging',
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_event_type',
      'social_topic',
      'filter',
      'comment',
      'simple_oauth',
      'simple_oauth_static_scope',
      'user',
      'taxonomy_access_fix',
      'social_editor',
      'group',
      'gnode',
      'grequest',
      'activity_creator',
      'activity_logger',
      'social_group',
      'profile',
      'social_profile',
      'layout_builder',
      'layout_discovery',
      'social_group_invite',
      'ginvite',
      'social_group_flexible_group',
      'social_group_request',
      'social_organization',
      'flag',
    ]);

    // Set up OAuth keys for testing.
    $this->setUpKeys();

    // Configure simple_oauth to use static scope provider.
    $this->config('simple_oauth.settings')
      ->set('scope_provider', 'static')
      ->save();

    if (!NodeType::load('topic')) {
      $node_type = NodeType::create([
        'type' => 'topic',
        'name' => 'Topic',
        'description' => 'Topic content type',
      ]);
      $node_type->save();
    }

    if (!Vocabulary::load('topic_types')) {
      $vocabulary = Vocabulary::create([
        'vid' => 'topic_types',
        'name' => 'Topic Types',
        'description' => 'Topic types vocabulary',
      ]);
      $vocabulary->save();
    }

    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();
  }

  /**
   * Test querying a content tag by ID.
   */
  public function testQueryContentTagById(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();

    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category->id(),
    ]);
    $tag->save();

    $this->assertResults(
      <<<GQL
        query ContentTag(\$id: ID!) {
          contentTag(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $tag->uuid()],
      [
        'contentTag' => [
          'id' => $tag->uuid(),
          'label' => 'Web Development',
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($tag)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying a content tag with parent relationship.
   */
  public function testQueryContentTagWithParent(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();

    // Create a child tag.
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category->id(),
    ]);
    $tag->save();

    $this->assertResults(
      <<<GQL
        query ContentTag(\$id: ID!) {
          contentTag(id: \$id) {
            id
            label
            parent {
              id
              label
            }
          }
        }
        GQL,
      ['id' => $tag->uuid()],
      [
        'contentTag' => [
          'id' => $tag->uuid(),
          'label' => 'Mobile Development',
          'parent' => [
            'id' => $category->uuid(),
            'label' => 'Technology',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($tag)
        ->addCacheableDependency($category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying a non-existent content tag returns null.
   */
  public function testQueryContentTagNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $fake_uuid = '12345678-1234-1234-1234-123456789012';

    $this->assertResults(
      <<<GQL
        query ContentTag(\$id: ID!) {
          contentTag(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $fake_uuid],
      [
        'contentTag' => NULL,
      ],
      $this->defaultCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying content tag with invalid UUID format.
   */
  public function testQueryContentTagInvalidUuid(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $invalid_uuid = 'not-a-valid-uuid';

    $this->assertResults(
      <<<GQL
        query ContentTag(\$id: ID!) {
          contentTag(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $invalid_uuid],
      [
        'contentTag' => NULL,
      ],
      $this->defaultCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying content tag without required ID argument fails.
   */
  public function testQueryContentTagMissingId(): void {
    $metadata = new CacheableMetadata();
    $metadata->setCacheMaxAge(0);
    $this->assertErrors(
      <<<GQL
        query ContentTag {
          contentTag {
            id
            label
          }
        }
        GQL,
      [],
      [
        '/Field "contentTag" argument "id" of type "ID!" is required/',
      ],
      $metadata
    );
  }

  /**
   * Test querying multiple content tags.
   */
  public function testQueryMultipleContentTags(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'parent' => 0,
    ]);
    $category->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Physics',
      'parent' => $category->id(),
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Chemistry',
      'parent' => $category->id(),
    ]);
    $tag2->save();

    $this->assertResults(
      <<<GQL
        query ContentTags(\$id1: ID!, \$id2: ID!) {
          tag1: contentTag(id: \$id1) {
            id
            label
            parent {
              label
            }
          }
          tag2: contentTag(id: \$id2) {
            id
            label
            parent {
              label
            }
          }
        }
        GQL,
      [
        'id1' => $tag1->uuid(),
        'id2' => $tag2->uuid(),
      ],
      [
        'tag1' => [
          'id' => $tag1->uuid(),
          'label' => 'Physics',
          'parent' => [
            'label' => 'Science',
          ],
        ],
        'tag2' => [
          'id' => $tag2->uuid(),
          'label' => 'Chemistry',
          'parent' => [
            'label' => 'Science',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($tag1)
        ->addCacheableDependency($tag2)
        ->addCacheableDependency($category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test content tag without parent (should handle gracefully).
   */
  public function testQueryContentTagWithoutParent(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Orphan Tag',
      'parent' => 0,
    ]);
    $tag->save();

    $this->assertResults(
      <<<GQL
        query ContentTag(\$id: ID!) {
          contentTag(id: \$id) {
            id
            label
            parent {
              id
              label
            }
          }
        }
        GQL,
      ['id' => $tag->uuid()],
      [
        'contentTag' => [
          'id' => $tag->uuid(),
          'label' => 'Orphan Tag',
          'parent' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($tag)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
