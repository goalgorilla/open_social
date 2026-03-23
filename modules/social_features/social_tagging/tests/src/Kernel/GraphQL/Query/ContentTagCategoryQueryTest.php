<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL\Query;

use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for the contentTagCategory GraphQL query.
 *
 * @group social_tagging
 */
class ContentTagCategoryQueryTest extends SocialGraphQLTestBase {

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
   * Test querying a content tag category by ID.
   */
  public function testQueryContentTagCategoryById(): void {
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
      'status' => 1,
    ]);
    $category->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $category->uuid()],
      [
        'contentTagCategory' => [
          'id' => $category->uuid(),
          'label' => 'Technology',
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying a non-existent content tag category returns null.
   */
  public function testQueryContentTagCategoryNotFound(): void {
    $fake_uuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $fake_uuid],
      [
        'contentTagCategory' => NULL,
      ],
      $this->defaultCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying content tag category with invalid UUID format.
   */
  public function testQueryContentTagCategoryInvalidUuid(): void {
    $invalid_uuid = 'not-a-valid-uuid';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            id
            label
          }
        }
        GQL,
      ['id' => $invalid_uuid],
      [
        'contentTagCategory' => NULL,
      ],
      $this->defaultCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying category with children tags using pagination.
   */
  public function testQueryContentTagCategoryWithChildren(): void {
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
      'status' => 1,
    ]);
    $category->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category->id(),
      'weight' => 0,
      'status' => 1,
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category->id(),
      'weight' => 1,
      'status' => 1,
    ]);
    $tag2->save();

    $tag3 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'DevOps',
      'parent' => $category->id(),
      'weight' => 2,
      'status' => 1,
    ]);
    $tag3->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            id
            label
            contentTags(first: 2) {
              edges {
                node {
                  id
                  label
                }
              }
              nodes {
                id
                label
              }
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
            }
          }
        }
        GQL,
      ['id' => $category->uuid()],
      [
        'contentTagCategory' => [
          'id' => $category->uuid(),
          'label' => 'Technology',
          'contentTags' => [
            'edges' => [
              [
                'node' => [
                  'id' => $tag1->uuid(),
                  'label' => 'Web Development',
                ],
              ],
              [
                'node' => [
                  'id' => $tag2->uuid(),
                  'label' => 'Mobile Development',
                ],
              ],
            ],
            'nodes' => [
              [
                'id' => $tag1->uuid(),
                'label' => 'Web Development',
              ],
              [
                'id' => $tag2->uuid(),
                'label' => 'Mobile Development',
              ],
            ],
            'pageInfo' => [
              'hasNextPage' => TRUE,
              'hasPreviousPage' => FALSE,
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($category)
        ->addCacheableDependency($tag1)
        ->addCacheableDependency($tag2)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test pagination with after cursor.
   */
  public function testQueryContentTagCategoryWithPagination(): void {
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
      'weight' => 0,
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Chemistry',
      'parent' => $category->id(),
      'weight' => 1,
    ]);
    $tag2->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            contentTags(first: 1) {
              edges {
                node {
                  id
                  label
                }
              }
              pageInfo {
                hasNextPage
              }
            }
          }
        }
        GQL,
      ['id' => $category->uuid()],
      [
        'contentTagCategory' => [
          'contentTags' => [
            'edges' => [
              [
                'node' => [
                  'id' => $tag1->uuid(),
                  'label' => 'Physics',
                ],
              ],
            ],
            'pageInfo' => [
              'hasNextPage' => TRUE,
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($category)
        ->addCacheableDependency($tag1)
        ->addCacheContexts(['languages:language_interface'])
    );

    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($category) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              query ContentTagCategory(\$id: ID!) {
                contentTagCategory(id: \$id) {
                  contentTags(first: 1) {
                    pageInfo {
                      endCursor
                    }
                  }
                }
              }
              GQL,
            'variables' => [
              'id' => $category->uuid(),
            ],
          ])
        );
      }
    );
    $end_cursor = $result->data['contentTagCategory']['contentTags']['pageInfo']['endCursor'];

    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!, \$after: Cursor!) {
          contentTagCategory(id: \$id) {
            contentTags(first: 1, after: \$after) {
              edges {
                node {
                  id
                  label
                }
              }
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
            }
          }
        }
        GQL,
      [
        'id' => $category->uuid(),
        'after' => $end_cursor,
      ],
      [
        'contentTagCategory' => [
          'contentTags' => [
            'edges' => [
              [
                'node' => [
                  'id' => $tag2->uuid(),
                  'label' => 'Chemistry',
                ],
              ],
            ],
            'pageInfo' => [
              'hasNextPage' => FALSE,
              'hasPreviousPage' => TRUE,
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($category)
        ->addCacheableDependency($tag2)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test empty child tags result.
   */
  public function testQueryContentTagCategoryWithNoChildren(): void {
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Empty Category',
      'parent' => 0,
    ]);
    $category->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query ContentTagCategory(\$id: ID!) {
          contentTagCategory(id: \$id) {
            id
            label
            contentTags(first: 10) {
              edges {
                node {
                  id
                  label
                }
              }
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
            }
          }
        }
        GQL,
      ['id' => $category->uuid()],
      [
        'contentTagCategory' => [
          'id' => $category->uuid(),
          'label' => 'Empty Category',
          'contentTags' => [
            'edges' => [],
            'pageInfo' => [
              'hasNextPage' => FALSE,
              'hasPreviousPage' => FALSE,
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($category)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
