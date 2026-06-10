<?php

declare(strict_types=1);

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GraphQL\Server\OperationParams;

/**
 * Tests GraphQL comment access when the parent comment field is Hidden.
 *
 * @group social_graphql
 * @group social_comment
 */
class QueryHiddenCommentFieldTest extends SocialGraphQLTestBase {

  use CommentTestTrait;
  use NodeCreationTrait;
  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'social_user',
    'user',
    'role_delegation',
    'node',
    'field',
    'text',
    'filter',
    'file',
    'image',
    'social_comment',
    'comment',
    'menu_ui',
    'entity_access_by_field',
    'options',
    'taxonomy',
    'path',
    'image_widget_crop',
    'crop',
    'field_group',
    'social_node',
    'social_core',
    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',
    'views_bulk_operations',
    'group',
    'variationcache',
    'flexible_permissions',
    'social_topic',
    'social_tagging',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_topic',
      'filter',
      'comment',
      'social_comment',
      'social_tagging',
    ]);

    $this->setUpGenericNodeWithCommentField();
  }

  /**
   * Data provider: entity bundles with a hideable comment field.
   *
   * @return array<string, array{0: array<string, mixed>}>
   *   Scenario key => setup metadata.
   */
  public static function hiddenCommentFieldProvider(): array {
    return [
      'topic' => [[
        'bundle' => 'topic',
        'comment_field' => 'field_topic_comments',
        'visibility_permission' => 'view node.topic.field_content_visibility:public content',
        'create_permission' => 'create topic content',
      ],
      ],
      'generic_node' => [[
        'bundle' => 'content_with_visibility',
        'comment_field' => 'field_test_comments',
        'visibility_permission' => 'view node.content_with_visibility.field_content_visibility:public content',
        'create_permission' => 'create content_with_visibility content',
      ],
      ],
    ];
  }

  /**
   * Bundles exposed on GraphQL with a parent { comments } connection.
   *
   * @return array<string, array{0: array<string, mixed>}>
   *   Scenario key => setup metadata.
   */
  public static function hiddenCommentFieldBundleGraphqlProvider(): array {
    return [
      'topic' => [[
        'bundle' => 'topic',
        'comment_field' => 'field_topic_comments',
        'visibility_permission' => 'view node.topic.field_content_visibility:public content',
        'create_permission' => 'create topic content',
        'graphql_entity' => 'topic',
      ],
      ],
    ];
  }

  /**
   * Comment(id) must not return a comment when the parent hides comments.
   *
   * @dataProvider hiddenCommentFieldProvider
   */
  public function testRegularUserCannotQueryCommentWhenFieldHidden(array $scenario): void {
    [$parent, $comment] = $this->createParentWithPublishedComment($scenario);

    $this->setCommentFieldHidden($parent, $scenario['comment_field']);
    $this->setUpRegularMember($scenario['visibility_permission']);

    $query = '
      query ($id: ID!) {
        comment(id: $id) {
          id
        }
      }
    ';
    $variables = ['id' => $comment->uuid()];

    $this->assertResults(
      $query,
      $variables,
      ['comment' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($parent)
        ->addCacheableDependency($comment)
        ->addCacheContexts(['languages:language_interface', 'user'])
        ->addCacheTags(['access_policies'])
    );
  }

  /**
   * Bundle comments connection must be empty when comments are hidden.
   *
   * @dataProvider hiddenCommentFieldBundleGraphqlProvider
   */
  public function testRegularUserCannotQueryParentCommentsWhenHidden(array $scenario): void {
    [$parent, $comment] = $this->createParentWithPublishedComment($scenario);

    $this->setCommentFieldHidden($parent, $scenario['comment_field']);
    $this->setUpRegularMember($scenario['visibility_permission']);

    $entity = $scenario['graphql_entity'];
    $query = "
      query (\$id: ID!) {
        {$entity}(id: \$id) {
          comments(first: 10) {
            nodes {
              id
            }
          }
        }
      }
    ";
    $variables = ['id' => $parent->uuid()];

    $this->assertCommentListQueryDoesNotExposeComment(
      $query,
      $variables,
      (string) $comment->uuid(),
      "{$entity}.comments",
      $scenario['comment_field'],
    );
  }

  /**
   * Root comments() must not list comments on entities with hidden comments.
   *
   * @dataProvider hiddenCommentFieldProvider
   */
  public function testRegularUserCannotQueryHiddenCommentInCommentsList(array $scenario): void {
    [$parent, $comment] = $this->createParentWithPublishedComment($scenario);

    $this->setCommentFieldHidden($parent, $scenario['comment_field']);
    $this->setUpRegularMember($scenario['visibility_permission']);

    $query = '
      query {
        comments(first: 50) {
          nodes {
            id
          }
        }
      }
    ';

    $this->assertCommentListQueryDoesNotExposeComment(
      $query,
      [],
      (string) $comment->uuid(),
      'comments',
      $scenario['comment_field'],
    );
  }

  /**
   * Installs a generic node type with a non-standard comment field name.
   */
  private function setUpGenericNodeWithCommentField(): void {
    if (NodeType::load('content_with_visibility') === NULL) {
      NodeType::create([
        'type' => 'content_with_visibility',
        'name' => 'Content with visibility',
      ])->save();
    }

    if (FieldConfig::load('node.content_with_visibility.field_content_visibility') === NULL) {
      FieldConfig::create([
        'field_name' => 'field_content_visibility',
        'entity_type' => 'node',
        'bundle' => 'content_with_visibility',
        'label' => 'Visibility',
        'required' => TRUE,
      ])->save();
    }

    if (FieldStorageConfig::load('node.field_test_comments') === NULL) {
      FieldStorageConfig::create([
        'field_name' => 'field_test_comments',
        'entity_type' => 'node',
        'type' => 'comment',
        'settings' => ['comment_type' => 'comment'],
        'module' => 'comment',
      ])->save();
    }

    if (FieldConfig::load('node.content_with_visibility.field_test_comments') === NULL) {
      FieldConfig::create([
        'field_name' => 'field_test_comments',
        'entity_type' => 'node',
        'bundle' => 'content_with_visibility',
        'label' => 'Comments',
        'settings' => [
          'default_mode' => 1,
          'per_page' => 50,
        ],
      ])->save();
    }
  }

  /**
   * Creates a published parent entity with one published comment.
   *
   * @param array<string, mixed> $scenario
   *   Provider scenario metadata.
   *
   * @return array{0: \Drupal\node\NodeInterface, 1: \Drupal\comment\Entity\Comment}
   *   Parent node and comment.
   */
  private function createParentWithPublishedComment(array $scenario): array {
    $permissions = [
      'administer comments',
      'skip comment approval',
      $scenario['create_permission'],
      $scenario['visibility_permission'],
    ];

    $this->setUpCurrentUser([], array_merge($permissions, $this->userPermissions()));

    $values = [
      'type' => $scenario['bundle'],
      'field_content_visibility' => 'public',
      'status' => NodeInterface::PUBLISHED,
    ];

    if ($scenario['bundle'] === 'topic') {
      $topic_types = Vocabulary::load('topic_types');
      $this->assertNotNull($topic_types);
      $values['field_topic_type'] = $this->createTerm($topic_types)->id();
    }

    $parent = $this->createNode($values);

    $comment = Comment::create([
      'entity_id' => $parent->id(),
      'entity_type' => 'node',
      'field_name' => $scenario['comment_field'],
      'comment_type' => 'comment',
      'status' => Comment::PUBLISHED,
      'field_comment_body' => 'Comment on entity with hidden comment field.',
    ]);
    $comment->save();

    self::assertNotNull($comment->uuid());

    return [$parent, $comment];
  }

  /**
   * Sets the comment field status to Hidden on the parent node.
   */
  private function setCommentFieldHidden(NodeInterface $parent, string $comment_field): void {
    $parent->set($comment_field, [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $parent->save();
  }

  /**
   * Sets the current user to a regular member (permissions only).
   */
  private function setUpRegularMember(string $visibility_permission): void {
    $this->setUpCurrentUser([], array_merge(
      [
        'access comments',
        $visibility_permission,
      ],
      $this->userPermissions(),
    ));
  }

  /**
   * Asserts a comments connection does not include the given comment UUID.
   */
  private function assertCommentListQueryDoesNotExposeComment(
    string $query,
    array $variables,
    string $commentUuid,
    string $endpoint,
    string $comment_field,
  ): void {
    $result = $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => $variables,
    ]));

    $this->assertEmpty(
      $result->errors,
      sprintf('GraphQL errors on %s: %s', $endpoint, json_encode($result->errors))
    );

    $nodes = $this->extractCommentNodeIds($result->data ?? []);
    $this->assertNotContains(
      $commentUuid,
      $nodes,
      sprintf(
        '%s must not expose comment UUID "%s" when %s is Hidden. Returned: %s',
        $endpoint,
        $commentUuid,
        $comment_field,
        $nodes === [] ? '(none)' : implode(', ', $nodes),
      )
    );
  }

  /**
   * Collects comment UUIDs from any comments { nodes { id } } shape in data.
   *
   * @return string[]
   *   Comment UUIDs found in the response tree.
   */
  private function extractCommentNodeIds(array $data): array {
    $ids = [];
    foreach ($data as $key => $value) {
      if ($key === 'comments' && is_array($value) && isset($value['nodes']) && is_array($value['nodes'])) {
        foreach ($value['nodes'] as $node) {
          if (isset($node['id'])) {
            $ids[] = (string) $node['id'];
          }
        }
      }
      elseif (is_array($value)) {
        $ids = array_merge($ids, $this->extractCommentNodeIds($value));
      }
    }
    return $ids;
  }

}
