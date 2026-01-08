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
 * Test coverage for the contentTag GraphQL query.
 *
 * @group social_tagging
 */
class ContentTagQueryTest extends SocialGraphQLTestBase {

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
   * Test querying a content tag by ID.
   */
  public function testQueryContentTagById(): void {
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

    // Create a child tag.
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Web Development',
      'parent' => $category->id(),
    ]);
    $tag->save();
    $cache_metadata->addCacheableDependency($tag);

    // Execute query manually to validate result.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($tag) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query ContentTag(\$id: ID!) {
                    contentTag(id: \$id) {
                        id
                        label
                    }
                }
                GQL,
            'variables' => [
              'id' => $tag->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertEquals($tag->uuid(), $result->data['contentTag']['id']);
    $this->assertEquals('Web Development', $result->data['contentTag']['label']);
  }

  /**
   * Test querying a content tag with parent relationship.
   */
  public function testQueryContentTagWithParent(): void {
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

    // Create a child tag.
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Mobile Development',
      'parent' => $category->id(),
    ]);
    $tag->save();
    $cache_metadata->addCacheableDependency($tag);

    // Execute query manually to validate result.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($tag) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id' => $tag->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertEquals($tag->uuid(), $result->data['contentTag']['id']);
    $this->assertEquals('Mobile Development', $result->data['contentTag']['label']);
    $this->assertEquals($category->uuid(), $result->data['contentTag']['parent']['id']);
    $this->assertEquals('Technology', $result->data['contentTag']['parent']['label']);
  }

  /**
   * Test querying a non-existent content tag returns null.
   */
  public function testQueryContentTagNotFound(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheTags(['taxonomy_term_list']);

    // Use a valid UUID format but non-existent tag.
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
      $cache_metadata
    );
  }

  /**
   * Test querying content tag with invalid UUID format.
   */
  public function testQueryContentTagInvalidUuid(): void {
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
      $cache_metadata
    );
  }

  /**
   * Test querying content tag without required ID argument fails.
   */
  public function testQueryContentTagMissingId(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Query without providing the required ID argument should fail validation.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
                query ContentTag {
                    contentTag {
                        id
                        label
                    }
                }
                GQL,
          ])
        );
      }
    );

    // Should have errors because ID is required.
    $this->assertNotEmpty($result->errors);
  }

  /**
   * Test querying multiple content tags.
   */
  public function testQueryMultipleContentTags(): void {
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
    ]);
    $tag1->save();
    $cache_metadata->addCacheableDependency($tag1);

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Chemistry',
      'parent' => $category->id(),
    ]);
    $tag2->save();
    $cache_metadata->addCacheableDependency($tag2);

    // Execute query for both tags.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($tag1, $tag2) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id1' => $tag1->uuid(),
              'id2' => $tag2->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertEquals($tag1->uuid(), $result->data['tag1']['id']);
    $this->assertEquals('Physics', $result->data['tag1']['label']);
    $this->assertEquals('Science', $result->data['tag1']['parent']['label']);
    $this->assertEquals($tag2->uuid(), $result->data['tag2']['id']);
    $this->assertEquals('Chemistry', $result->data['tag2']['label']);
    $this->assertEquals('Science', $result->data['tag2']['parent']['label']);
  }

  /**
   * Test content tag without parent (should handle gracefully).
   */
  public function testQueryContentTagWithoutParent(): void {
    $this->actAsClientCredentialsWithScopes(['content_tag:view']);

    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);

    // Create a term without parent (edge case).
    $tag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Orphan Tag',
      'parent' => 0,
    ]);
    $tag->save();
    $cache_metadata->addCacheableDependency($tag);

    // Execute query.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($tag) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
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
            'variables' => [
              'id' => $tag->uuid(),
            ],
          ])
        );
      }
    );

    $this->assertEmpty($result->errors);
    $this->assertEquals($tag->uuid(), $result->data['contentTag']['id']);
    $this->assertEquals('Orphan Tag', $result->data['contentTag']['label']);
    // Parent should be null when there's no parent.
    $this->assertNull($result->data['contentTag']['parent']);
  }

}
