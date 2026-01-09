<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL;

use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for ContentTaggableSelectedTagCategories GraphQL type.
 *
 * @group social_tagging
 */
class ContentTaggableSelectedTagCategoriesTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;
  use NodeCreationTrait;

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
    // Modules required by social_core.
    'crop',
    'image_widget_crop',
    'image_effects',
    'file_mdm',
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
    $this->installEntitySchema('oauth2_token');
    $this->installEntitySchema('oauth2_scope');
    $this->installEntitySchema('consumer');
    // Install meeting_api_meeting entity schema required by social_event.
    $this->installEntitySchema('meeting_api_meeting');

    // Install schemas required by social_event.
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installSchema('file', 'file_usage');

    $this->installConfig([
      'taxonomy',
      'social_tagging',
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_topic',
      'simple_oauth',
      'simple_oauth_static_scope',
      'filter',
      'comment',
    ]);

    // Configure OAuth to use static scope provider and set up keys.
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    // Set up user with permissions to view events and topics.
    $this->setUpCurrentUser(["uid" => 1], [
      'view terms in social_tagging',
      'view node.event.field_content_visibility:public content',
      'view node.topic.field_content_visibility:public content',
      'access content',
    ], TRUE);
  }

  /**
   * Test querying contentTagCategories for an Event with tags.
   */
  public function testQueryContentTagCategoriesForEvent(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create category terms.
    $category1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category1->save();
    $cache_metadata->addCacheableDependency($category1);

    $category2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'parent' => 0,
    ]);
    $category2->save();
    $cache_metadata->addCacheableDependency($category2);

    // Create tags under category1.
    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category1->id(),
      'weight' => 0,
    ]);
    $tag1->save();
    $cache_metadata->addCacheableDependency($tag1);

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category1->id(),
      'weight' => 1,
    ]);
    $tag2->save();
    $cache_metadata->addCacheableDependency($tag2);

    // Create a tag under category2.
    $tag3 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Physics',
      'parent' => $category2->id(),
      'weight' => 0,
    ]);
    $tag3->save();
    $cache_metadata->addCacheableDependency($tag3);

    // Create an Event with tags from both categories.
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
    $cache_metadata->addCacheableDependency($event);

    // Execute query.
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
            'variables' => [
              'id' => $event->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotNull($result->data['event'], 'Event should be returned');
    $this->assertNotEmpty($result->data['event']['contentTagCategories']);
    $this->assertCount(2, $result->data['event']['contentTagCategories']);

    // Find categories in result.
    $tech_category = NULL;
    $science_category = NULL;
    foreach ($result->data['event']['contentTagCategories'] as $category_data) {
      if ($category_data['category']['id'] === $category1->uuid()) {
        $tech_category = $category_data;
      }
      elseif ($category_data['category']['id'] === $category2->uuid()) {
        $science_category = $category_data;
      }
    }

    $this->assertNotNull($tech_category, 'Technology category should be present');
    $this->assertNotNull($science_category, 'Science category should be present');

    // Verify Technology category has 2 tags.
    $this->assertCount(2, $tech_category['contentTags']['edges']);
    $tag_ids = array_column($tech_category['contentTags']['nodes'], 'id');
    $this->assertContains($tag1->uuid(), $tag_ids);
    $this->assertContains($tag2->uuid(), $tag_ids);

    // Verify Science category has 1 tag.
    $this->assertCount(1, $science_category['contentTags']['edges']);
    $this->assertEquals($tag3->uuid(), $science_category['contentTags']['nodes'][0]['id']);
    $this->assertEquals('Physics', $science_category['contentTags']['nodes'][0]['label']);
  }

  /**
   * Test querying contentTagCategories for an Event with no tags.
   */
  public function testQueryContentTagCategoriesForEventWithNoTags(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create an Event without tags.
    $event = $this->createNode([
      'type' => 'event',
      'title' => 'Test Event Without Tags',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
    ]);
    $cache_metadata->addCacheableDependency($event);

    // Execute query.
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
            'variables' => [
              'id' => $event->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertNotNull($result->data['event'], 'Event should be returned');
    $this->assertIsArray($result->data['event']['contentTagCategories']);
    $this->assertEmpty($result->data['event']['contentTagCategories']);
  }

  /**
   * Test pagination for contentTags in ContentTaggableSelectedTagCategories.
   */
  public function testContentTagCategoriesPagination(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a category.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Create multiple tags.
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
      $cache_metadata->addCacheableDependency($tag);
    }

    // Create an Event with all tags.
    $tag_ids = array_map(fn($tag) => ['target_id' => $tag->id()], $tags);
    $event = $this->createNode([
      'type' => 'event',
      'title' => 'Test Event',
      'status' => NodeInterface::PUBLISHED,
      'field_content_visibility' => 'public',
      'social_tagging' => $tag_ids,
    ]);
    $cache_metadata->addCacheableDependency($event);

    // Execute query with pagination (first: 2).
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
                            category {
                                id
                            }
                            contentTags(first: 2) {
                                edges {
                                    cursor
                                    node {
                                        id
                                        label
                                    }
                                }
                                pageInfo {
                                    hasNextPage
                                    hasPreviousPage
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

    $this->assertEmpty($result->errors);
    $this->assertNotNull($result->data['event'], 'Event should be returned');
    $category_data = $result->data['event']['contentTagCategories'][0];
    $this->assertCount(2, $category_data['contentTags']['edges']);
    $this->assertTrue($category_data['contentTags']['pageInfo']['hasNextPage']);
    $this->assertFalse($category_data['contentTags']['pageInfo']['hasPreviousPage']);

    // Get next page using cursor.
    $end_cursor = $category_data['contentTags']['pageInfo']['endCursor'];
    $result2 = $renderer->executeInRenderContext(
      $context,
      function () use ($event, $end_cursor) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id' => $event->uuid(),
              'after' => $end_cursor,
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result2->errors);
    $category_data2 = $result2->data['event']['contentTagCategories'][0];
    $this->assertCount(2, $category_data2['contentTags']['edges']);
    $this->assertTrue($category_data2['contentTags']['pageInfo']['hasNextPage']);
    $this->assertTrue($category_data2['contentTags']['pageInfo']['hasPreviousPage']);
  }

  /**
   * Test querying contentTagCategories for a Topic with tags.
   */
  public function testQueryContentTagCategoriesForTopic(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a category.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Create a tag.
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category->id(),
    ]);
    $tag->save();
    $cache_metadata->addCacheableDependency($tag);

    // Create a Topic with the tag.
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
    $cache_metadata->addCacheableDependency($topic);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id' => $topic->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertCount(1, $result->data['topic']['contentTagCategories']);
    $category_data = $result->data['topic']['contentTagCategories'][0];
    $this->assertEquals($category->uuid(), $category_data['category']['id']);
    $this->assertEquals('Technology', $category_data['category']['label']);
    $this->assertCount(1, $category_data['contentTags']['nodes']);
    $this->assertEquals($tag->uuid(), $category_data['contentTags']['nodes'][0]['id']);
    $this->assertEquals('Web Development', $category_data['contentTags']['nodes'][0]['label']);
  }

  /**
   * Test querying with both parent and child tags selected.
   *
   * When a parent tag (top-level) and a child tag are both selected,
   * the parent tag should appear as a category with empty contentTags,
   * and the child tag should appear with its parent category.
   */
  public function testQueryContentTagCategoriesWithParentAndChildTags(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent tag (top-level category).
    $parent_tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Tag 1',
      'parent' => 0,
    ]);
    $parent_tag->save();
    $cache_metadata->addCacheableDependency($parent_tag);

    // Create a child tag under the parent.
    $child_tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Tag 1.1',
      'parent' => $parent_tag->id(),
      'weight' => 0,
    ]);
    $child_tag->save();
    $cache_metadata->addCacheableDependency($child_tag);

    // Create a Topic with both parent and child tags selected.
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
    $cache_metadata->addCacheableDependency($topic);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id' => $topic->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    // Should have only one category entry (grouped by category).
    $this->assertCount(1, $result->data['topic']['contentTagCategories']);
    $category_data = $result->data['topic']['contentTagCategories'][0];

    // The category should be the parent tag.
    $this->assertEquals($parent_tag->uuid(), $category_data['category']['id']);
    $this->assertEquals('Tag 1', $category_data['category']['label']);

    // contentTags should only contain the child tag, not the parent.
    $this->assertCount(1, $category_data['contentTags']['nodes']);
    $this->assertEquals($child_tag->uuid(), $category_data['contentTags']['nodes'][0]['id']);
    $this->assertEquals('Tag 1.1', $category_data['contentTags']['nodes'][0]['label']);

    // Verify the parent tag is NOT in contentTags.
    $tag_ids = array_column($category_data['contentTags']['nodes'], 'id');
    $this->assertNotContains($parent_tag->uuid(), $tag_ids);
  }

}
