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
 * Test coverage for the contentTagCategory GraphQL query.
 *
 * @group social_tagging
 */
class ContentTagCategoryQueryTest extends SocialGraphQLTestBase {

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

    $this->installConfig([
      'taxonomy',
      'social_tagging',
      'simple_oauth',
      'simple_oauth_static_scope',
    ]);

    // Configure OAuth to use static scope provider and set up keys.
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    $this->setUpCurrentUser(["uid" => 1], ['view terms in social_tagging'], TRUE);
  }

  /**
   * Test querying a content tag category by ID.
   */
  public function testQueryContentTagCategoryById(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent category term.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Execute query manually to validate result.
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
                        id
                        label
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

    $this->assertEmpty($result->errors);
    $this->assertEquals($category->uuid(), $result->data['contentTagCategory']['id']);
    $this->assertEquals('Technology', $result->data['contentTagCategory']['label']);
  }

  /**
   * Test querying a non-existent content tag category returns null.
   */
  public function testQueryContentTagCategoryNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheTags(['taxonomy_term_list']);

    // Use a valid UUID format but non-existent category.
    $fake_uuid = '12345678-1234-1234-1234-123456789012';

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
      $cache_metadata
    );
  }

  /**
   * Test querying content tag category with invalid UUID format.
   */
  public function testQueryContentTagCategoryInvalidUuid(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheTags(['taxonomy_term_list']);

    // Use an invalid UUID format.
    $invalid_uuid = 'not-a-valid-uuid';

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
      $cache_metadata
    );
  }

  /**
   * Test querying category with children tags using pagination.
   */
  public function testQueryContentTagCategoryWithChildren(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent category term.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Create child tags.
    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category->id(),
      'weight' => 0,
    ]);
    $tag1->save();
    $cache_metadata->addCacheableDependency($tag1);

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category->id(),
      'weight' => 1,
    ]);
    $tag2->save();
    $cache_metadata->addCacheableDependency($tag2);

    $tag3 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'DevOps',
      'parent' => $category->id(),
      'weight' => 2,
    ]);
    $tag3->save();
    $cache_metadata->addCacheableDependency($tag3);

    // Execute query manually to validate result.
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
                        id
                        label
                        contentTags(first: 2) {
                            edges {
                                cursor
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
                                startCursor
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

    $this->assertEmpty($result->errors);
    $this->assertEquals($category->uuid(), $result->data['contentTagCategory']['id']);
    $this->assertEquals('Technology', $result->data['contentTagCategory']['label']);
    $this->assertCount(2, $result->data['contentTagCategory']['contentTags']['edges']);
    $this->assertEquals($tag1->uuid(), $result->data['contentTagCategory']['contentTags']['edges'][0]['node']['id']);
    $this->assertEquals('Web Development', $result->data['contentTagCategory']['contentTags']['edges'][0]['node']['label']);
    $this->assertEquals($tag2->uuid(), $result->data['contentTagCategory']['contentTags']['edges'][1]['node']['id']);
    $this->assertEquals('Mobile Development', $result->data['contentTagCategory']['contentTags']['edges'][1]['node']['label']);
    $this->assertTrue($result->data['contentTagCategory']['contentTags']['pageInfo']['hasNextPage']);
    $this->assertFalse($result->data['contentTagCategory']['contentTags']['pageInfo']['hasPreviousPage']);
  }

  /**
   * Test pagination with after cursor.
   */
  public function testQueryContentTagCategoryWithPagination(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent category term.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Create child tags.
    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Physics',
      'parent' => $category->id(),
      'weight' => 0,
    ]);
    $tag1->save();
    $cache_metadata->addCacheableDependency($tag1);

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Chemistry',
      'parent' => $category->id(),
      'weight' => 1,
    ]);
    $tag2->save();
    $cache_metadata->addCacheableDependency($tag2);

    // Get first page.
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
                            edges {
                                cursor
                                node {
                                    id
                                    label
                                }
                            }
                            pageInfo {
                                hasNextPage
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

    $this->assertEmpty($result->errors);
    $this->assertNotEmpty($result->data['contentTagCategory']['contentTags']['edges']);
    $end_cursor = $result->data['contentTagCategory']['contentTags']['pageInfo']['endCursor'];
    $this->assertTrue($result->data['contentTagCategory']['contentTags']['pageInfo']['hasNextPage']);

    // Get second page using cursor.
    $result2 = $renderer->executeInRenderContext(
      $context,
      function () use ($category, $end_cursor) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query ContentTagCategory(\$id: ID!, \$after: Cursor!) {
                    contentTagCategory(id: \$id) {
                        contentTags(first: 1, after: \$after) {
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
                            }
                        }
                    }
                }
                GQL,
            'variables' => [
              'id' => $category->uuid(),
              'after' => $end_cursor,
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result2->errors);
    $this->assertEquals($tag2->uuid(), $result2->data['contentTagCategory']['contentTags']['edges'][0]['node']['id']);
    $this->assertEquals('Chemistry', $result2->data['contentTagCategory']['contentTags']['edges'][0]['node']['label']);
    $this->assertFalse($result2->data['contentTagCategory']['contentTags']['pageInfo']['hasNextPage']);
    $this->assertTrue($result2->data['contentTagCategory']['contentTags']['pageInfo']['hasPreviousPage']);
  }

  /**
   * Test empty child tags result.
   */
  public function testQueryContentTagCategoryWithNoChildren(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a parent category term with no children.
    $category = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Empty Category',
      'parent' => 0,
    ]);
    $category->save();
    $cache_metadata->addCacheableDependency($category);

    // Execute query.
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
            'variables' => [
              'id' => $category->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertEquals($category->uuid(), $result->data['contentTagCategory']['id']);
    $this->assertEmpty($result->data['contentTagCategory']['contentTags']['edges']);
    $this->assertFalse($result->data['contentTagCategory']['contentTags']['pageInfo']['hasNextPage']);
    $this->assertFalse($result->data['contentTagCategory']['contentTags']['pageInfo']['hasPreviousPage']);
  }

}
