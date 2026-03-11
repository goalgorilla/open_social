<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Core\Render\RenderContext;
use Drupal\social_group\Entity\Group;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_graphql\Kernel\GraphQLOAuthTestTrait;
use Drupal\Tests\social_graphql\Kernel\OAuthTestTrait;
use Drupal\Tests\social_topic\Kernel\SocialTopicGraphQLKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Test coverage for the updateTopic GraphQL mutation.
 *
 * Organization-related behavior is covered in
 * UpdateTopicOrganizationMutationTest.
 *
 * @group social_topic
 */
class UpdateTopicMutationTest extends SocialTopicGraphQLKernelTestBase {

  use OAuthTestTrait;
  use UserCreationTrait;
  use GraphQLOAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('social_group.settings')
      ->set('cross_posting.status', TRUE)
      ->set('cross_posting.content_types', ['topic'])
      ->set('cross_posting.group_types', ['flexible_group'])
      ->save();
  }

  /**
   * Minimal valid Rich Text JSON body (paragraph with text).
   */
  private static function minimalRichTextBody(): array {
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

  /**
   * Test updating a topic with all fields.
   */
  public function testUpdateTopicSuccess(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $clientMutationId = '550e8400-e29b-41d4-a716-446655440000';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$type: ID, \$title: String, \$visibility: ContentVisibility, \$clientMutationId: UUIDv4) {
          updateTopic(input: {
            clientMutationId: \$clientMutationId
            id: \$id
            type: \$type
            title: \$title
            visibility: \$visibility
          }) {
            clientMutationId
            errors
            topic {
              id
              title
              visibility
            }
          }
        }
        GQL,
      [
        'clientMutationId' => $clientMutationId,
        'id' => $topic->uuid(),
        'type' => $topicType->uuid(),
        'title' => 'Updated Title',
        'visibility' => 'PUBLIC',
      ],
      [
        'updateTopic' => [
          'clientMutationId' => $clientMutationId,
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title',
            'visibility' => 'PUBLIC',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the node was actually updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals('Updated Title', $updated_topic->getTitle());
    $this->assertEquals('public', $updated_topic->get('field_content_visibility')->value);
  }

  /**
   * Test updating a topic with only title (partial update).
   */
  public function testUpdateTopicPartialUpdate(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();
    $original_body = $topic->get('body')->value;
    $original_visibility = $topic->get('field_content_visibility')->value;

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$title: String) {
          updateTopic(input: {
            id: \$id
            title: \$title
          }) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'title' => 'Updated Title Only',
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title Only',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify other fields remain unchanged.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals($original_body, $updated_topic->get('body')->value);
    $this->assertEquals($original_visibility, $updated_topic->get('field_content_visibility')->value);
  }

  /**
   * Test updating a topic's body with Rich Text JSON.
   */
  public function testUpdateTopicWithBody(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => 'Original body', 'format' => 'basic_html']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$body: RichTextJSON) {
          updateTopic(input: {
            id: \$id
            body: \$body
          }) {
            errors
            topic {
              id
              bodyHtml
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'body' => self::minimalRichTextBody(),
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'bodyHtml' => "<div><p>Hello</p>\n</div>",
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheTags(['node:' . $topic->id(), 'config:filter.format.basic_html'])
        ->addCacheContexts(['languages:language_interface'])
        ->setCacheMaxAge(0)
    );
  }

  /**
   * Test validation error for missing topic ID.
   *
   * Note: Since id is required in the GraphQL schema (ID!), GraphQL will
   * return a schema validation error before the mutation executes.
   */
  public function testUpdateTopicMissingId(): void {
    // Execute mutation without ID - Will return schema validation error.
    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation UpdateTopic(\$input: UpdateTopicInput!) {
                updateTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'title' => 'Test Title',
              ],
            ],
          ])
        );
      }
    );

    // GraphQL schema validation error because id is required.
    $this->assertNotEmpty($result->errors, 'Expected GraphQL schema validation error for missing required field id');
  }

  /**
   * Test validation error for invalid topic ID.
   */
  public function testUpdateTopicInvalidId(): void {
    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $fakeUuid,
          'title' => 'Test Title',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TOPIC_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for title too long (> 255 characters).
   */
  public function testUpdateTopicTitleTooLong(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Create a title longer than 255 characters.
    $longTitle = str_repeat('A', 256);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => $longTitle,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TITLE_TOO_LONG'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for empty title.
   */
  public function testUpdateTopicEmptyTitle(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Discussion',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => '',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TITLE_REQUIRED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid topic type UUID.
   */
  public function testUpdateTopicInvalidTopicType(): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Use a non-existent UUID.
    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'type' => $fakeUuid,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['TOPIC_TYPE_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Data provider for visibility test cases.
   *
   * @return array
   *   Array of visibility values to test.
   */
  public function provideVisibilities(): array {
    return [
      'PUBLIC' => ['PUBLIC'],
      'COMMUNITY' => ['COMMUNITY'],
      'GROUP_MEMBER' => ['GROUP_MEMBER'],
    ];
  }

  /**
   * Test updating topic with different visibility levels.
   *
   * @param string $visibility
   *   The visibility level to test.
   *
   * @dataProvider provideVisibilities
   */
  public function testUpdateTopicDifferentVisibilities(string $visibility): void {
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'General',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => "Topic with $visibility visibility",
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // GROUP_MEMBER requires at least one group, so create it.
    if ($visibility === 'GROUP_MEMBER') {
      $group = Group::create([
        'type' => 'flexible_group',
        'label' => 'Test Group for visibility',
        'field_group_allowed_visibility' => ['public', 'community', 'group'],
      ]);
      $group->save();
      $group->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);
    }

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              visibility
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => $visibility,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'visibility' => $visibility,
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that updating a topic requires the topic:write scope.
   */
  public function testUpdateTopicRequiresTopicWriteScope(): void {
    // Act as client credentials without the required scope.
    // Create a topic type.
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Execute mutation without the required scope.
    $this->actAsClientCredentialsWithScopes([]);
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $result = $renderer->executeInRenderContext(
      $context,
      function () use ($topic) {
        return $this->server->executeOperation(
          OperationParams::create([
            'query' => <<<GQL
              mutation UpdateTopic(\$input: UpdateTopicInput!) {
                updateTopic(input: \$input) {
                  errors
                  topic {
                    id
                  }
                }
              }
              GQL,
            'variables' => [
              'input' => [
                'id' => $topic->uuid(),
                'title' => 'Test Topic',
              ],
            ],
          ])
        );
      }
    );

    // Should have GraphQL errors about missing scope.
    $this->assertNotEmpty($result->errors);
    $errorMessages = array_map(
      fn($error) => is_object($error) && method_exists($error, 'getMessage')
        ? $error->getMessage()
        : (is_array($error) ? json_encode($error) : (string) $error),
      $result->errors
    );
    $errorMessage = implode(' | ', $errorMessages);
    $this->assertStringContainsString("Missing scope 'topic:write'", $errorMessage, 'Expected error about missing topic:write scope');

    // Verify the topic title remains unchanged.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $updated_topic = $node_storage->load($topic_id);
    assert($updated_topic instanceof NodeInterface);
    $this->assertEquals('Original Title', $updated_topic->getTitle(), 'Topic title should remain unchanged after scope failure');
  }

  /**
   * Test updating a topic with content tags.
   */
  public function testUpdateTopicWithContentTags(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Technology',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $tag2 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Innovation',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag2->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            contentTags: \$contentTags
          }) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'contentTags' => [$tag1->uuid(), $tag2->uuid()],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify the content tags were actually updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($tag1->id(), $tag_ids, 'Tag 1 should be assigned to topic');
    $this->assertContains($tag2->id(), $tag_ids, 'Tag 2 should be assigned to topic');
  }

  /**
   * Test validation error for invalid content tag UUID (empty or non-string).
   */
  public function testUpdateTopicWithEmptyContentTagUuid(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $emptyUuid = '';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$emptyUuid],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_INPUT'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for invalid content tag UUID (not found).
   */
  public function testUpdateTopicWithInvalidContentTags(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'News',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $fakeUuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$fakeUuid],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ["CONTENT_TAG_NOT_FOUND:$fakeUuid"],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating topic with partial update including content tags.
   */
  public function testUpdateTopicPartialUpdateWithContentTags(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Blog',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Science',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();
    $original_visibility = $topic->get('field_content_visibility')->value;

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$title: String, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            title: \$title
            contentTags: \$contentTags
          }) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'title' => 'Updated Title with Tags',
        'contentTags' => [$tag1->uuid()],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title with Tags',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify both title and content tags were updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $this->assertEquals('Updated Title with Tags', $updated_topic->getTitle());
    $this->assertEquals($original_visibility, $updated_topic->get('field_content_visibility')->value, 'Visibility should remain unchanged');
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($tag1->id(), $tag_ids, 'Tag should be assigned to topic');
  }

  /**
   * Test removing content tags by passing empty array.
   */
  public function testUpdateTopicRemoveContentTags(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Discussion',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Education',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'social_tagging' => [$tag1->id()],
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            contentTags: \$contentTags
          }) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'contentTags' => [],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify content tags were removed.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values_after = $updated_topic->get('social_tagging')->getValue();
    $this->assertEmpty($tag_values_after, 'Content tags should be removed');
  }

  /**
   * Test removing content tags by passing explicit null (clear request).
   */
  public function testUpdateTopicClearContentTagsWithNull(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Discussion',
    ]);
    $topicType->save();

    $tag1 = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Education',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tag1->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'social_tagging' => [$tag1->id()],
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$id: ID!, \$contentTags: [ID!]) {
          updateTopic(input: {
            id: \$id
            contentTags: \$contentTags
          }) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'id' => $topic->uuid(),
        'contentTags' => NULL,
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    // Verify content tags were cleared (explicit null = clear, not omit).
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values_after = $updated_topic->get('social_tagging')->getValue();
    $this->assertEmpty($tag_values_after, 'Content tags should be cleared when contentTags: null');
  }

  /**
   * Test validation error when providing too many content tags.
   */
  public function testUpdateTopicTooManyContentTags(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $fake_tag_uuids = [];
    for ($i = 0; $i < 200; $i++) {
      $fake_tag_uuids[] = sprintf('12345678-1234-1234-1234-%012d', $i);
    }

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => $fake_tag_uuids,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAGS_LIMIT_EXCEEDED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error for content tag with invalid usage (not for topics).
   */
  public function testUpdateTopicWithInvalidContentTagUsage(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $tagA = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Tag',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $tagA->save();

    $tagB = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Topic Tag',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $tagB->save();

    $tagC = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Shared Tag',
      'field_category_usage' => serialize(['node_event', 'node_topic']),
      'status' => 1,
    ]);
    $tagC->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$tagA->uuid(), $tagB->uuid(), $tagC->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $tagA->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$tagB->uuid(), $tagC->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test topic with child tag that inherits field_category_usage from parent.
   */
  public function testUpdateTopicWithChildTagInheritingFromParent(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $parentTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Parent Category',
      'field_category_usage' => serialize(['node_topic']),
      'status' => 1,
    ]);
    $parentTag->save();

    $childTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Child Tag',
      'parent' => [$parentTag->id()],
      'status' => 1,
    ]);
    $childTag->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Original Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
    );

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    /** @var \Drupal\node\NodeInterface $updated_topic */
    $updated_topic = $node_storage->load($topic_id);
    $tag_values = $updated_topic->get('social_tagging')->getValue();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tag_ids = [];
    foreach ($tag_values as $value) {
      if (!empty($value['target_id'])) {
        $term = $term_storage->load($value['target_id']);
        if ($term !== NULL) {
          $tag_ids[] = $term->id();
        }
      }
    }
    $this->assertContains($childTag->id(), $tag_ids, 'Child tag should be assigned to topic');
  }

  /**
   * Test tag without category_usage is rejected when parent don't allow topic.
   */
  public function testUpdateTopicWithChildTagRejectedWhenParentDoesNotAllowTopic(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $parentTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Parent Category',
      'field_category_usage' => serialize(['node_event']),
      'status' => 1,
    ]);
    $parentTag->save();

    $childTag = Term::create([
      'vid' => 'social_tagging',
      'name' => 'Event Child Tag',
      'parent' => [$parentTag->id()],
      'status' => 1,
    ]);
    $childTag->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'community',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertTrue(
      $childTag->get('field_category_usage')->isEmpty(),
      'Child tag should not have field_category_usage configured'
    );

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'contentTags' => [$childTag->uuid()],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CONTENT_TAG_INVALID_USAGE:' . $childTag->uuid()],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test adding a topic to a group when editing.
   */
  public function testUpdateTopicAddToGroup(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create a topic without groups.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic Without Group',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    // Create a group with allowed visibility options.
    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'community'],
    ]);
    $group->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic Without Group',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationship was created.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group->id(), $group_ids, 'Topic should be linked to the group.');
  }

  /**
   * Test removing a topic from all groups when editing.
   */
  public function testUpdateTopicRemoveFromAllGroups(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create groups.
    $group1 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 1',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group1->save();

    $group2 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 2',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group2->save();

    // Create a topic in groups.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic in Groups',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [
        ['target_id' => $group1->id()],
        ['target_id' => $group2->id()],
      ],
      'status' => 1,
    ]);
    $topic->save();

    // Add group relationships manually using Group::addRelationship.
    $group1->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);
    $group2->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => NULL,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic in Groups',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationships were removed.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $this->assertEmpty($relationships, 'Topic should not be linked to any groups.');
  }

  /**
   * Test changing which groups a topic is in when editing.
   */
  public function testUpdateTopicChangeGroups(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create groups.
    $group1 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 1',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group1->save();

    $group2 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 2',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group2->save();

    // Create a topic in group1.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic in Group 1',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group1->id()]],
      'status' => 1,
    ]);
    $topic->save();

    // Add group relationship manually using Group::addRelationship.
    $group1->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group2->uuid(),
            'crosspostedGroups' => [],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic in Group 1',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationships were updated.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group2->id(), $group_ids, 'Topic should be linked to group 2.');
    $this->assertNotContains($group1->id(), $group_ids, 'Topic should not be linked to group 1.');
  }

  /**
   * Test leaving group membership unchanged when editing other fields.
   */
  public function testUpdateTopicLeaveGroupsUnchanged(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create a group.
    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group->save();

    // Create a topic in a group.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group->id()]],
      'status' => 1,
    ]);
    $topic->save();

    // Add group relationship manually using Group::addRelationship.
    $group->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'title' => 'Updated Title',
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Updated Title',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationship remains unchanged.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group->id(), $group_ids, 'Topic should still be linked to the group.');
  }

  /**
   * Test validation error when trying to assign topic to invalid group.
   */
  public function testUpdateTopicWithInvalidGroup(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $fakeGroupUuid = '12345678-1234-1234-1234-123456789012';

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $fakeGroupUuid,
            'crosspostedGroups' => [],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['GROUP_NOT_FOUND'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when trying to assign topic to duplicate groups.
   */
  public function testUpdateTopicWithDuplicateGroups(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [$group->uuid()],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['GROUP_DUPLICATE'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test updating topic with cross-posting successfully.
   */
  public function testUpdateTopicWithCrossPostingSuccess(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create groups with shared visibility options.
    $group1 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 1',
      'field_group_allowed_visibility' => ['public', 'community'],
    ]);
    $group1->save();

    $group2 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 2',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group2->save();

    // Create a topic in group1.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic in Group 1',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group1->id()]],
      'status' => 1,
    ]);
    $topic->save();

    // Add group relationship manually using Group::addRelationship.
    $group1->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic in Group 1',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationships were created.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group1->id(), $group_ids, 'Topic should be linked to group 1.');
    $this->assertContains($group2->id(), $group_ids, 'Topic should be linked to group 2.');
  }

  /**
   * Test validation error when visibility is not allowed in groups.
   */
  public function testUpdateTopicVisibilityNotAllowedInGroups(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Group allows only PUBLIC and GROUP_MEMBERS, not COMMUNITY.
    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => 'COMMUNITY',
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['VISIBILITY_NOT_ALLOWED_IN_GROUP'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test validation error when cross-posting is disabled.
   */
  public function testUpdateTopicCrossPostingDisabled(): void {
    // Disable cross-posting for this test.
    $this->config('social_group.settings')
      ->set('cross_posting.status', FALSE)
      ->save();

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group1 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 1',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group1->save();

    $group2 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 2',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group2->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group1->uuid(),
            'crosspostedGroups' => [$group2->uuid()],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['CROSS_POSTING_IS_DISABLED'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    // Re-enable cross-posting for other tests.
    $this->config('social_group.settings')
      ->set('cross_posting.status', TRUE)
      ->save();
  }

  /**
   * Test updating topic from one group to multiple groups with cross-posting.
   */
  public function testUpdateTopicFromSingleToMultipleGroups(): void {
    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    // Create groups.
    $group1 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 1',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group1->save();

    $group2 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 2',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group2->save();

    $group3 = Group::create([
      'type' => 'flexible_group',
      'label' => 'Group 3',
      'field_group_allowed_visibility' => ['public'],
    ]);
    $group3->save();

    // Create a topic in group1.
    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Topic in Group 1',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group1->id()]],
      'status' => 1,
    ]);
    $topic->save();

    // Add group relationship manually using Group::addRelationship.
    $group1->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertResults(
      <<<GQL
        mutation UpdateTopic(\$input: UpdateTopicInput!) {
          updateTopic(input: \$input) {
            errors
            topic {
              id
              title
            }
          }
        }
        GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'group' => $group2->uuid(),
            'crosspostedGroups' => [$group3->uuid()],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'title' => 'Topic in Group 1',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    // Verify group relationships were updated.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $updated_topic = $node_storage->load($topic->id());
    assert($updated_topic instanceof NodeInterface);
    $relationships = GroupRelationship::loadByEntity($updated_topic);
    $group_ids = array_map(function ($relationship) {
      return $relationship->getGroupId();
    }, $relationships);
    $this->assertContains($group2->id(), $group_ids, 'Topic should be linked to group 2.');
    $this->assertContains($group3->id(), $group_ids, 'Topic should be linked to group 3.');
    $this->assertNotContains($group1->id(), $group_ids, 'Topic should not be linked to group 1.');
  }

  /**
   * Test error when updating visibility to GROUP_MEMBER without groups.
   */
  public function testUpdateTopicToGroupMemberVisibilityWithoutGroups(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => 'GROUP_MEMBER',
          // No groups provided.
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['GROUP_REQUIRED_FOR_GROUP_VISIBILITY'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $node_storage->resetCache([$topic_id]);
    $reloaded_topic = $node_storage->load($topic_id);
    assert($reloaded_topic instanceof NodeInterface);
    $this->assertSame('public', $reloaded_topic->get('field_content_visibility')->value);
  }

  /**
   * Test error when removing groups from topic with GROUP_MEMBER visibility.
   */
  public function testUpdateTopicRemoveGroupsFromGroupMemberVisibility(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'group',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group->id()]],
      'status' => 1,
    ]);
    $topic->save();
    $group->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => NULL,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['GROUP_REQUIRED_FOR_GROUP_VISIBILITY'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $node_storage->resetCache([$topic_id]);
    $reloaded_topic = $node_storage->load($topic_id);
    assert($reloaded_topic instanceof NodeInterface);
    $this->assertSame('group', $reloaded_topic->get('field_content_visibility')->value);
    $relationships = GroupRelationship::loadByEntity($reloaded_topic);
    $group_ids = array_map(fn ($rel) => $rel->getGroupId(), $relationships);
    $this->assertContains($group->id(), $group_ids, 'Topic should still be linked to the group after failed mutation.');
  }

  /**
   * Test error when updating visibility to GROUP_MEMBER and removing groups.
   */
  public function testUpdateTopicToGroupMemberVisibilityAndRemoveGroups(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'groups' => [['target_id' => $group->id()]],
      'status' => 1,
    ]);
    $topic->save();
    $group->addRelationship($topic, 'group_node:topic', ['uid' => $topic->getOwnerId()]);

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic {
            id
          }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => 'GROUP_MEMBER',
          'groups' => NULL,
        ],
      ],
      [
        'updateTopic' => [
          'errors' => ['GROUP_REQUIRED_FOR_GROUP_VISIBILITY'],
          'topic' => NULL,
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $node_storage->resetCache([$topic_id]);
    $reloaded_topic = $node_storage->load($topic_id);
    assert($reloaded_topic instanceof NodeInterface);
    $this->assertSame('public', $reloaded_topic->get('field_content_visibility')->value);
    $relationships = GroupRelationship::loadByEntity($reloaded_topic);
    $group_ids = array_map(fn ($rel) => $rel->getGroupId(), $relationships);
    $this->assertContains($group->id(), $group_ids, 'Topic should still be linked to the group after failed mutation.');
  }

  /**
   * Test updating to GROUP_MEMBER visibility works when groups are provided.
   */
  public function testUpdateTopicToGroupMemberVisibilityWithGroups(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertResults(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic {
            id
            visibility
          }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'visibility' => 'GROUP_MEMBER',
          'groups' => [
            'group' => $group->uuid(),
            'crosspostedGroups' => [],
          ],
        ],
      ],
      [
        'updateTopic' => [
          'errors' => NULL,
          'topic' => [
            'id' => $topic->uuid(),
            'visibility' => 'GROUP_MEMBER',
          ],
        ],
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheableDependency($topic)
        ->addCacheContexts(['languages:language_interface'])
        ->addCacheTags(['node:' . $topic->id()])
    );

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $topic_id = $topic->id();
    assert($topic_id !== NULL);
    $node_storage->resetCache([$topic_id]);
    $reloaded_topic = $node_storage->load($topic_id);
    assert($reloaded_topic instanceof NodeInterface);
    $this->assertEquals('group', $reloaded_topic->get('field_content_visibility')->value);
  }

  /**
   * Test error when groups input has crosspostedGroups but no primary group.
   *
   * CrosspostedGroups can only be used when a primary group is also provided.
   */
  public function testUpdateTopicGroupsWithCrosspostedGroupsOnlyRejected(): void {
    $this->actAsClientCredentialsWithScopes(['topic:write']);

    $topicType = Term::create([
      'vid' => 'topic_types',
      'name' => 'Article',
    ]);
    $topicType->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
      'field_group_allowed_visibility' => ['public', 'group'],
    ]);
    $group->save();

    $topic = Node::create([
      'type' => 'topic',
      'title' => 'Original Title',
      'body' => [['value' => ' ']],
      'field_content_visibility' => 'public',
      'field_topic_type' => $topicType->id(),
      'status' => 1,
    ]);
    $topic->save();

    $this->assertErrors(
      <<<GQL
      mutation UpdateTopic(\$input: UpdateTopicInput!) {
        updateTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'id' => $topic->uuid(),
          'groups' => [
            'crosspostedGroups' => [$group->uuid()],
          ],
        ],
      ],
      [
        '/groups\.group.*required|was not provided/i',
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
