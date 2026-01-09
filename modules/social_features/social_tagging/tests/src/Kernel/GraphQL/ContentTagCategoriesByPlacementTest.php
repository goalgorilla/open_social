<?php

declare(strict_types=1);

namespace Drupal\Tests\social_tagging\Kernel\GraphQL;

use Drupal\Core\Render\RenderContext;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\iata_graphql_user\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\iata_graphql_user\Kernel\OAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for topicTagCategories and eventTagCategories GraphQL queries.
 *
 * @group social_tagging
 */
class ContentTagCategoriesByPlacementTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
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

    $this->setUpCurrentUser(["uid" => 1], ['view terms in social_tagging'], TRUE);
  }

  /**
   * Test topicTagCategories returns only categories with TOPIC placement.
   */
  public function testQueryTopicTagCategories(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a category for topics.
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();
    $cache_metadata->addCacheableDependency($topic_category);

    // Create a category for events.
    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();
    $cache_metadata->addCacheableDependency($event_category);

    // Create a category for both.
    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();
    $cache_metadata->addCacheableDependency($both_category);

    // Create a category without placement (should not appear).
    $no_placement_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'No Placement Category',
      'parent' => 0,
    ]);
    $no_placement_category->save();
    $cache_metadata->addCacheableDependency($no_placement_category);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query TopicTagCategories {
                    topicTagCategories {
                        id
                        label
                        placement
                    }
                }
                GQL,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertIsArray($result->data['topicTagCategories']);

    // Should return topic_category and both_category,
    // but not event_category or no_placement_category.
    $returned_ids = array_column($result->data['topicTagCategories'], 'id');
    $this->assertContains($topic_category->uuid(), $returned_ids);
    $this->assertContains($both_category->uuid(), $returned_ids);
    $this->assertNotContains($event_category->uuid(), $returned_ids);
    $this->assertNotContains($no_placement_category->uuid(), $returned_ids);

    // Verify placement values.
    foreach ($result->data['topicTagCategories'] as $category) {
      $this->assertIsArray($category['placement']);
      $this->assertContains('TOPIC', $category['placement']);
    }
  }

  /**
   * Test eventTagCategories returns only categories with EVENT placement.
   */
  public function testQueryEventTagCategories(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a category for topics.
    $topic_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $topic_category->save();
    $cache_metadata->addCacheableDependency($topic_category);

    // Create a category for events.
    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();
    $cache_metadata->addCacheableDependency($event_category);

    // Create a category for both.
    $both_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Both Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic', 'node_event']),
    ]);
    $both_category->save();
    $cache_metadata->addCacheableDependency($both_category);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query EventTagCategories {
                    eventTagCategories {
                        id
                        label
                        placement
                    }
                }
                GQL,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertIsArray($result->data['eventTagCategories']);

    // Should return event_category and both_category, but not topic_category.
    $returned_ids = array_column($result->data['eventTagCategories'], 'id');
    $this->assertContains($event_category->uuid(), $returned_ids);
    $this->assertContains($both_category->uuid(), $returned_ids);
    $this->assertNotContains($topic_category->uuid(), $returned_ids);

    // Verify placement values.
    foreach ($result->data['eventTagCategories'] as $category) {
      $this->assertIsArray($category['placement']);
      $this->assertContains('EVENT', $category['placement']);
    }
  }

  /**
   * Test that unpublished categories are not returned.
   */
  public function testQueryTopicTagCategoriesExcludesUnpublished(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a published category for topics.
    $published_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Published Topic Category',
      'parent' => 0,
      'status' => 1,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $published_category->save();
    $cache_metadata->addCacheableDependency($published_category);

    // Create an unpublished category for topics.
    $unpublished_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Unpublished Topic Category',
      'parent' => 0,
      'status' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $unpublished_category->save();
    $cache_metadata->addCacheableDependency($unpublished_category);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query TopicTagCategories {
                    topicTagCategories {
                        id
                        label
                    }
                }
                GQL,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertIsArray($result->data['topicTagCategories']);

    // Should only return published category.
    $returned_ids = array_column($result->data['topicTagCategories'], 'id');
    $this->assertContains($published_category->uuid(), $returned_ids);
    $this->assertNotContains($unpublished_category->uuid(), $returned_ids);
  }

  /**
   * Test that only parent terms (parent = 0) are returned.
   */
  public function testQueryTopicTagCategoriesOnlyParentTerms(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent category for topics.
    $parent_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Parent Topic Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $parent_category->save();
    $cache_metadata->addCacheableDependency($parent_category);

    // Create a child term (should not appear in results).
    $child_term = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Child Term',
      'parent' => $parent_category->id(),
      'field_category_usage' => serialize(['node_topic']),
    ]);
    $child_term->save();
    $cache_metadata->addCacheableDependency($child_term);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query TopicTagCategories {
                    topicTagCategories {
                        id
                        label
                    }
                }
                GQL,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertIsArray($result->data['topicTagCategories']);

    // Should only return parent category, not child term.
    $returned_ids = array_column($result->data['topicTagCategories'], 'id');
    $this->assertContains($parent_category->uuid(), $returned_ids);
    $this->assertNotContains($child_term->uuid(), $returned_ids);
  }

  /**
   * Test empty result when no categories match the placement.
   */
  public function testQueryTopicTagCategoriesEmptyResult(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheTags(['taxonomy_term_list']);

    // Create a category for events only.
    $event_category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Category',
      'parent' => 0,
      'field_category_usage' => serialize(['node_event']),
    ]);
    $event_category->save();
    $cache_metadata->addCacheableDependency($event_category);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query TopicTagCategories {
                    topicTagCategories {
                        id
                        label
                    }
                }
                GQL,
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertIsArray($result->data['topicTagCategories']);
    $this->assertEmpty($result->data['topicTagCategories']);
  }

}
