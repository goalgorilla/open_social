<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL\Query;

use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for ContentTaggableSelectedTagCategories GraphQL type.
 *
 * @group social_tagging
 */
class ContentTaggableSelectedTagCategoriesTest extends SocialGraphQLTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;
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
    'media',
    'inline_entity_form',
    'social_media_system',
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
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('meeting_api_meeting');
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
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
   * Test querying contentTagCategories for an Event with tags.
   */
  public function testQueryContentTagCategoriesForEvent(): void {
    $category1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category1->save();

    $category2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'parent' => 0,
    ]);
    $category2->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category1->id(),
      'weight' => 0,
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category1->id(),
      'weight' => 1,
    ]);
    $tag2->save();

    $tag3 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Physics',
      'parent' => $category2->id(),
      'weight' => 0,
    ]);
    $tag3->save();

    $event = $this->createNode([
      'type' => 'event',
      'title' => 'Test Event',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
      'social_tagging' => [
        ['target_id' => $tag1->id()],
        ['target_id' => $tag2->id()],
        ['target_id' => $tag3->id()],
      ],
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        query Event(\$id: ID!) {
          event(id: \$id) {
            id
            contentTagCategories {
              category {
                id
                label
              }
              contentTags(first: 10) {
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
              }
            }
          }
        }
        GQL,
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'contentTagCategories' => [
            [
              'category' => [
                'id' => $category1->uuid(),
                'label' => 'Technology',
              ],
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
              ],
            ],
            [
              'category' => [
                'id' => $category2->uuid(),
                'label' => 'Science',
              ],
              'contentTags' => [
                'edges' => [
                  [
                    'node' => [
                      'id' => $tag3->uuid(),
                      'label' => 'Physics',
                    ],
                  ],
                ],
                'nodes' => [
                  [
                    'id' => $tag3->uuid(),
                    'label' => 'Physics',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($category1)
        ->addCacheableDependency($category2)
        ->addCacheableDependency($tag1)
        ->addCacheableDependency($tag2)
        ->addCacheableDependency($tag3)
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying contentTagCategories for an Event with no tags.
   */
  public function testQueryContentTagCategoriesForEventWithNoTags(): void {
    $event = $this->createNode([
      'type' => 'event',
      'title' => 'Test Event Without Tags',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        query Event(\$id: ID!) {
          event(id: \$id) {
            id
            contentTagCategories {
              category {
                id
                label
              }
              contentTags(first: 10) {
                edges {
                  node {
                    id
                    label
                  }
                }
              }
            }
          }
        }
        GQL,
      ['id' => $event->uuid()],
      [
        'event' => [
          'id' => $event->uuid(),
          'contentTagCategories' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test pagination for contentTags in ContentTaggableSelectedTagCategories.
   */
  public function testContentTagCategoriesPagination(): void {
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();

    $tags = [];
    for ($i = 0; $i < 5; $i++) {
      $tag = Term::create([
        'vid' => 'social_tagging',
        'name' => "Tag $i",
        'parent' => $category->id(),
        'weight' => $i,
      ]);
      $tag->save();
      $tags[] = $tag;
    }

    $tag_ids = array_map(fn($tag) => ['target_id' => $tag->id()], $tags);
    $event = $this->createNode([
      'type' => 'event',
      'title' => 'Test Event',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
      'social_tagging' => $tag_ids,
    ]);

    $this->actAsClientCredentialsWithScopes(['event:write']);
    $this->assertResults(
      <<<GQL
        query Event(\$id: ID!) {
          event(id: \$id) {
            contentTagCategories {
              category {
                id
              }
              contentTags(first: 2) {
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
        }
        GQL,
      ['id' => $event->uuid()],
      [
        'event' => [
          'contentTagCategories' => [
            [
              'category' => [
                'id' => $category->uuid(),
              ],
              'contentTags' => [
                'edges' => [
                  [
                    'node' => [
                      'id' => $tags[0]->uuid(),
                      'label' => 'Tag 0',
                    ],
                  ],
                  [
                    'node' => [
                      'id' => $tags[1]->uuid(),
                      'label' => 'Tag 1',
                    ],
                  ],
                ],
                'pageInfo' => [
                  'hasNextPage' => TRUE,
                  'hasPreviousPage' => FALSE,
                ],
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($category)
        ->addCacheableDependency($tags[0])
        ->addCacheableDependency($tags[1])
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );

    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($event) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              query Event(\$id: ID!) {
                event(id: \$id) {
                  contentTagCategories {
                    contentTags(first: 2) {
                      pageInfo {
                        endCursor
                      }
                    }
                  }
                }
              }
              GQL,
            'variables' => [
              'id' => $event->uuid(),
            ],
          ])
        );
      }
    );
    $end_cursor = $result->data['event']['contentTagCategories'][0]['contentTags']['pageInfo']['endCursor'];

    $this->assertResults(
      <<<GQL
        query Event(\$id: ID!, \$after: Cursor!) {
          event(id: \$id) {
            contentTagCategories {
              contentTags(first: 2, after: \$after) {
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
        }
        GQL,
      [
        'id' => $event->uuid(),
        'after' => $end_cursor,
      ],
      [
        'event' => [
          'contentTagCategories' => [
            [
              'contentTags' => [
                'edges' => [
                  [
                    'node' => [
                      'id' => $tags[2]->uuid(),
                      'label' => 'Tag 2',
                    ],
                  ],
                  [
                    'node' => [
                      'id' => $tags[3]->uuid(),
                      'label' => 'Tag 3',
                    ],
                  ],
                ],
                'pageInfo' => [
                  'hasNextPage' => TRUE,
                  'hasPreviousPage' => TRUE,
                ],
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($event)
        ->addCacheableDependency($category)
        ->addCacheableDependency($tags[2])
        ->addCacheableDependency($tags[3])
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying contentTagCategories for a Topic with tags.
   */
  public function testQueryContentTagCategoriesForTopic(): void {
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

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
      'social_tagging' => [
        ['target_id' => $tag->id()],
      ],
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query Topic(\$id: ID!) {
          topic(id: \$id) {
            id
            contentTagCategories {
              category {
                id
                label
              }
              contentTags(first: 10) {
                nodes {
                  id
                  label
                }
              }
            }
          }
        }
        GQL,
      ['id' => $topic->uuid()],
      [
        'topic' => [
          'id' => $topic->uuid(),
          'contentTagCategories' => [
            [
              'category' => [
                'id' => $category->uuid(),
                'label' => 'Technology',
              ],
              'contentTags' => [
                'nodes' => [
                  [
                    'id' => $tag->uuid(),
                    'label' => 'Web Development',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheableDependency($category)
        ->addCacheableDependency($tag)
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test querying with both parent and child tags selected.
   */
  public function testQueryContentTagCategoriesWithParentAndChildTags(): void {
    $parent_tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Tag 1',
      'parent' => 0,
    ]);
    $parent_tag->save();

    $child_tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Tag 1.1',
      'parent' => $parent_tag->id(),
      'weight' => 0,
    ]);
    $child_tag->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Test Topic With Parent And Child',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
      'social_tagging' => [
        ['target_id' => $parent_tag->id()],
        ['target_id' => $child_tag->id()],
      ],
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        query Topic(\$id: ID!) {
          topic(id: \$id) {
            id
            contentTagCategories {
              category {
                id
                label
              }
              contentTags(first: 10) {
                nodes {
                  id
                  label
                }
              }
            }
          }
        }
        GQL,
      ['id' => $topic->uuid()],
      [
        'topic' => [
          'id' => $topic->uuid(),
          'contentTagCategories' => [
            [
              'category' => [
                'id' => $parent_tag->uuid(),
                'label' => 'Tag 1',
              ],
              'contentTags' => [
                'nodes' => [
                  [
                    'id' => $child_tag->uuid(),
                    'label' => 'Tag 1.1',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheableDependency($parent_tag)
        ->addCacheableDependency($child_tag)
        ->addCacheTags([
          'group_content_list:plugin:group_node:event',
          'group_content_list:plugin:group_node:topic',
        ])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
