<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test coverage for topicTagCategories and eventTagCategories GraphQL queries.
 *
 * @group social_tagging
 */
class ContentTagCategoriesByPlacementTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use GraphQLOAuthTestTrait;
  use UserCreationTrait;

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
    'options',
    'social_event',
    'social_event_type',
    'social_topic',
    // Modules required for GraphQL User type.
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
    'menu_ui',
    'path',
    'block',
    'block_content',
    'group',
    'group_core_comments',
    'views',
    'taxonomy_access_fix',
    // Modules required by social_core.
    'crop',
    'image_widget_crop',
    'image_effects',
    'file_mdm',
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

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installSchema('layout_builder', ['inline_block_usage']);
    $this->installSchema('file', 'file_usage');
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
  }

  /**
   * AN: Test topicTagCategories returns only categories with TOPIC placement.
   */
  public function testQueryTopicTagCategoriesForAnonymous(): void {
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();

    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();

    $no_placement_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'No Placement Category',
      'parent' => 0,
    ]);
    $no_placement_category->save();

    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
            placement
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [
          [
            'id' => $topic_category->uuid(),
            'label' => 'Topic Category',
            'placement' => ['TOPIC'],
          ],
          [
            'id' => $both_category->uuid(),
            'label' => 'Both Category',
            'placement' => ['TOPIC', 'EVENT'],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic_category)
        ->addCacheableDependency($both_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * AN: Test topicTagCategories returns only categories with TOPIC placement.
   */
  public function testQueryTopicTagCategoriesForAuthorized(): void {
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();

    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();

    $no_placement_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'No Placement Category',
      'parent' => 0,
    ]);
    $no_placement_category->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
            placement
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [
          [
            'id' => $topic_category->uuid(),
            'label' => 'Topic Category',
            'placement' => ['TOPIC'],
          ],
          [
            'id' => $both_category->uuid(),
            'label' => 'Both Category',
            'placement' => ['TOPIC', 'EVENT'],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic_category)
        ->addCacheableDependency($both_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * AN: Test eventTagCategories returns only categories with EVENT placement.
   */
  public function testQueryEventTagCategoriesForAnonymous(): void {
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();

    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();

    $this->assertResults(
      <<<GQL
        query EventTagCategories {
          eventTagCategories {
            id
            label
            placement
          }
        }
        GQL,
      [],
      [
        'eventTagCategories' => [
          [
            'id' => $event_category->uuid(),
            'label' => 'Event Category',
            'placement' => ['EVENT'],
          ],
          [
            'id' => $both_category->uuid(),
            'label' => 'Both Category',
            'placement' => ['TOPIC', 'EVENT'],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event_category)
        ->addCacheableDependency($both_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * AN: Test eventTagCategories returns only categories with EVENT placement.
   */
  public function testQueryEventTagCategoriesForAuthorized(): void {
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();

    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        query EventTagCategories {
          eventTagCategories {
            id
            label
            placement
          }
        }
        GQL,
      [],
      [
        'eventTagCategories' => [
          [
            'id' => $event_category->uuid(),
            'label' => 'Event Category',
            'placement' => ['EVENT'],
          ],
          [
            'id' => $both_category->uuid(),
            'label' => 'Both Category',
            'placement' => ['TOPIC', 'EVENT'],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event_category)
        ->addCacheableDependency($both_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that unpublished categories are not returned.
   */
  public function testQueryTopicTagCategoriesExcludesUnpublished(): void {
    $published_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Published Topic Category',
      'parent' => 0,
      'status' => 1,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $published_category->save();

    $unpublished_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Unpublished Topic Category',
      'parent' => 0,
      'status' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $unpublished_category->save();

    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [
          [
            'id' => $published_category->uuid(),
            'label' => 'Published Topic Category',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($published_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that only parent terms (parent = 0) are returned (anonymous).
   */
  public function testQueryTopicTagCategoriesOnlyParentTermsForAnonymous(): void {
    $parent_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Parent Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $parent_category->save();

    $child_term = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Child Term',
      'parent' => $parent_category->id(),
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $child_term->save();

    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [
          [
            'id' => $parent_category->uuid(),
            'label' => 'Parent Topic Category',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($parent_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that only parent terms (parent = 0) are returned (authorized).
   */
  public function testQueryTopicTagCategoriesOnlyParentTermsForAuthorized(): void {
    $parent_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Parent Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $parent_category->save();

    $child_term = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Child Term',
      'parent' => $parent_category->id(),
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $child_term->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [
          [
            'id' => $parent_category->uuid(),
            'label' => 'Parent Topic Category',
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($parent_category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test empty result when no categories match the placement (anonymous).
   */
  public function testQueryTopicTagCategoriesEmptyResultForAnonymous(): void {
    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [],
      ],
      $this->defaultCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test empty result when no categories match the placement (authorized).
   */
  public function testQueryTopicTagCategoriesEmptyResultForAuthorized(): void {
    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query TopicTagCategories {
          topicTagCategories {
            id
            label
          }
        }
        GQL,
      [],
      [
        'topicTagCategories' => [],
      ],
      $this->defaultCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
