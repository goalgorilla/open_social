<?php

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;
use Drupal\social_post\Entity\Post;

/**
 * Tests the comments field on the Query type.
 *
 * @group social_graphql
 * @group social_comment
 */
class QueryCommentsTest extends SocialGraphQLTestBase {

  use CommentTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // For the comment functionality.
    'social_comment',
    'comment',
    // For checking access to a comment.
    'entity',
    // For the comment author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures comments for nodes.
    'node',
    // The default comment config contains a body text field.
    'field',
    'text',
    'filter',
    // Required modules.
    'views',
    'views_bulk_operations',
    'group',
    'variationcache',
    'flexible_permissions',
    // For node query access (status, visibility).
    'options',
    'entity_access_by_field',
    'social_node',
    // For comments on posts.
    'social_post',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('post');
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['filter', 'comment', 'social_comment', 'social_node']);
    $this->setUpContentWithVisibilityAndComments();
    $this->setUpPostCommentSupport();
  }

  /**
   * Installs minimal config for comments on posts (no full social_post config).
   */
  private function setUpPostCommentSupport(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    if ($entity_type_manager->getStorage('post_type')->load('post') === NULL) {
      $entity_type_manager->getStorage('post_type')->create([
        'id' => 'post',
        'label' => 'Post',
      ])->save();
    }
    if ($entity_type_manager->getStorage('comment_type')->load('post_comment') === NULL) {
      $entity_type_manager->getStorage('comment_type')->create([
        'id' => 'post_comment',
        'label' => 'Post comment',
        'target_entity_type_id' => 'post',
        'description' => 'Comment on a post',
      ])->save();
    }
    if ($entity_type_manager->getStorage('field_config')->load('comment.post_comment.field_comment_body') === NULL) {
      FieldConfig::create([
        'field_name' => 'field_comment_body',
        'entity_type' => 'comment',
        'bundle' => 'post_comment',
        'label' => 'Comment',
        'required' => TRUE,
        'settings' => [],
      ])->save();
    }
  }

  /**
   * Test that platform comments can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    $this->setUpCurrentUser([], array_merge([
      'skip comment approval',
      'access comments',
      'view node.content_with_visibility.field_content_visibility:public content',
    ], $this->userPermissions()));

    $node = $this->publicNode;

    $comments = [];
    for ($i = 0; $i < 10; ++$i) {
      $comments[] = $this->createComment($node, NULL, ['field_name' => 'field_test_comments', 'status' => 1]);
    }

    $comment_uuids = array_map(
      static fn($comment) => $comment->uuid(),
      $comments
    );

    $this->assertEndpointSupportsPagination(
      'comments',
      $comment_uuids
    );
  }

  /**
   * Test that the comments endpoint respects the access comments permission.
   */
  public function testUserRequiresAccessCommentsPermission() {
    $this->setUpCurrentUser([], array_merge([
      'skip comment approval',
      'access comments',
      'view node.content_with_visibility.field_content_visibility:public content',
    ], $this->userPermissions()));
    $this->createComment($this->publicNode, NULL, ['field_name' => 'field_test_comments']);

    $this->setUpCurrentUser([], $this->userPermissions());

    $this->assertResults('
        query {
          comments(first: 1) {
            nodes {
              id
            }
          }
        }
      ',
      [],
      [
        'comments' => [
          'nodes' => [],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Node with "public" visibility for visibility tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $publicNode;

  /**
   * Node with "community" visibility for visibility tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $communityNode;

  /**
   * Sets up content_with_visibility node with visibility and comment fields.
   */
  private function setUpContentWithVisibilityAndComments(): void {
    NodeType::create(['type' => 'content_with_visibility', 'name' => 'Content with visibility'])->save();
    FieldConfig::create([
      'field_name' => 'field_content_visibility',
      'entity_type' => 'node',
      'bundle' => 'content_with_visibility',
      'label' => 'Visibility',
      'required' => TRUE,
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_comments',
      'entity_type' => 'node',
      'type' => 'comment',
      'settings' => ['comment_type' => 'comment'],
      'module' => 'comment',
    ]);
    $field_storage->save();
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

    $this->publicNode = Node::create([
      'type' => 'content_with_visibility',
      'title' => 'Public',
      'field_content_visibility' => 'public',
      'status' => 1,
    ]);
    $this->publicNode->save();
    $this->communityNode = Node::create([
      'type' => 'content_with_visibility',
      'title' => 'Community',
      'field_content_visibility' => 'community',
      'status' => 1,
    ]);
    $this->communityNode->save();
  }

  /**
   * User with only public visibility sees only comments on public content.
   *
   * Comments on community-only nodes must not appear. Explicitly assert the
   * community comment is not in the result.
   */
  public function testUserWithPublicVisibilitySeesOnlyCommentsOnPublicContent(): void {
    $comment_on_public = $this->createComment($this->publicNode, NULL, ['field_name' => 'field_test_comments', 'status' => 1]);
    $this->createComment($this->communityNode, NULL, ['field_name' => 'field_test_comments', 'status' => 1]);

    $this->setUpCurrentUser([], array_merge([
      'view node.content_with_visibility.field_content_visibility:public content',
      'access comments',
    ], $this->userPermissions()));

    $query = '
      query {
        comments(first: 10) {
          nodes { id }
        }
      }
    ';
    $this->assertResults($query, [], [
      'comments' => [
        'nodes' => [
          ['id' => $comment_on_public->uuid()],
        ],
      ],
    ], $this->defaultCacheMetaData()
      ->setCacheMaxAge(0)
      ->addCacheContexts(['languages:language_interface'])
      ->addCacheableDependency($comment_on_public));
  }

  /**
   * Comments on posts are returned by the comments query.
   *
   * Comment query access allows comments on non-node entities (e.g. post);
   * only comments on nodes are restricted by node access.
   */
  public function testCommentsOnPostReturnedInQuery(): void {
    $account = $this->setUpCurrentUser([], array_merge([
      'skip comment approval',
      'access comments',
    ], $this->userPermissions()));

    $post = Post::create([
      'type' => 'post',
      'user_id' => $account->id(),
    ]);
    $post->save();

    $comment_on_post = $this->createComment(
      $post,
      NULL,
      [
        'comment_type' => 'post_comment',
        'field_name' => 'field_post_comments',
        'status' => 1,
        'field_comment_body' => [['value' => 'Post comment body']],
      ]
    );

    $query = '
      query {
        comments(first: 10) {
          nodes { id }
        }
      }
    ';
    $this->assertResults($query, [], [
      'comments' => [
        'nodes' => [
          ['id' => $comment_on_post->uuid()],
        ],
      ],
    ], $this->defaultCacheMetaData()
      ->setCacheMaxAge(0)
      ->addCacheContexts(['languages:language_interface'])
      ->addCacheableDependency($comment_on_post));
  }

  /**
   * Test that a user can only see comments they're allowed to see in the list.
   *
   * - Any published comment
   * - Their own unpublished comment.
   */
  public function testUserCanViewOnlyOwnOrOtherPublishedComments() {
    $node = $this->publicNode;
    $this->setUpCurrentUser([], array_merge([
      'access comments',
      'view node.content_with_visibility.field_content_visibility:public content',
    ], $this->userPermissions()));
    $this->createComment($node, NULL, ['field_name' => 'field_test_comments']);
    $published_comment = $this->createComment($node, NULL, ['field_name' => 'field_test_comments', 'status' => 1]);

    $this->setUpCurrentUser([], array_merge([
      'access comments',
      'view node.content_with_visibility.field_content_visibility:public content',
    ], $this->userPermissions()));
    $this->createComment($node, NULL, ['field_name' => 'field_test_comments']);

    $this->assertResults('
        query {
          comments(last: 3) {
            pageInfo {
              hasNextPage
              hasPreviousPage
            }
            nodes {
              id
            }
          }
        }
      ',
      [],
      [
        'comments' => [
          'pageInfo' => [
            'hasNextPage' => FALSE,
            'hasPreviousPage' => FALSE,
          ],
          'nodes' => [
            ['id' => $published_comment->uuid()],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->setCacheMaxAge(0)
        ->addCacheableDependency($published_comment)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Create the comment entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the comment is made on.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   An optional user to create the comment as.
   * @param mixed[] $values
   *   An optional array of values to pass to Comment::create.
   *
   * @return \Drupal\comment\CommentInterface
   *   Created comment entity.
   */
  private function createComment(EntityInterface $entity, ?AccountInterface $user = NULL, array $values = []) {
    if ($user !== NULL) {
      $values += ['uid' => $user->id()];
    }

    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = Comment::create(
      $values +
      [
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'comment_type' => 'comment',
        'field_name' => 'comments',
      ]
    );

    $comment->save();

    return $comment;
  }

  /**
   * Helper method to get cache for commentsCreated tets.
   */
  private function createMetadataForCommentsCreated(UserInterface $user): CacheableMetadata {
    $cache_metadata = $this->defaultCacheMetaData();
    $cache_metadata->setCacheContexts([
      'languages:language_interface',
      'user.permissions',
    ]);
    $cache_metadata->addCacheableDependency($user);
    $cache_metadata->addCacheTags(['comment_list']);

    return $cache_metadata;
  }

  /**
   * Helper method to get query for commentsCreated tests.
   */
  private function getQueryForCommentsCreated(): string {
    return '
      query ($id: ID!) {
        user(id: $id) {
          id
          commentsCreated
        }
      }
    ';
  }

  /**
   * Test that the default value for the commentsCreated count is zero.
   */
  public function testUserCreatedCommentsIsZero(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'commentsCreated' => 0,
      ],
    ];

    // Scenario: The default value for the count is zero.
    $this->assertResults(
      $this->getQueryForcommentsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForcommentsCreated($user)
    );
  }

  /**
   * Test that adding an event will increase the user's statistic count.
   */
  public function testUserCreatedCommentsCount(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    $this->createComment($this->publicNode, $user, ['field_name' => 'field_test_comments']);

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'commentsCreated' => 1,
      ],
    ];

    // Scenario: Adding an event will increase the user's statistic count.
    $this->assertResults(
      $this->getQueryForcommentsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForcommentsCreated($user)
    );

  }

  /**
   * Test that deleting an item is reflected in the number of Comments created.
   */
  public function testUserCreatedCommentsDeleted(): void {
    $user = $this->setUpCurrentUser([], array_merge(['administer users'], $this->userPermissions()));

    $comment = $this->createComment($this->publicNode, $user, ['field_name' => 'field_test_comments']);

    // Delete comment.
    $comment->delete();

    // Set expected array.
    $expected_data = [
      'user' => [
        'id' => $user->uuid(),
        'commentsCreated' => 0,
      ],
    ];

    // Scenario: Deleting an event is reflected in the number of Comments
    // created by the user.
    $this->assertResults(
      $this->getQueryForcommentsCreated(),
      ['id' => $user->uuid()],
      $expected_data,
      $this->createMetadataForcommentsCreated($user)
    );

  }

}
