<?php

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

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
  public static $modules = [
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
  ];


  /**
   * The list of comments.
   *
   * @var \Drupal\comment\CommentInterface[]
   */
  private $comments = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['filter', 'comment', 'social_comment']);
  }

  /**
   * Test that platform comments can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    // Act as a user that can create and view published comments and contents.
    $this->setUpCurrentUser([], array_merge(['skip comment approval', 'access comments'], $this->userPermissions()));

    // Create a node to comment on.
    $node = $this->createNode();

    // Create a bunch of test comments for pagination testing.
    $comments = [];
    for ($i = 0; $i < 10; ++$i) {
      $comments[] = $this->createComment($node);
    }

    $comment_uuids = array_map(
      static fn ($comment) => $comment->uuid(),
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
    // Create a published comment on a node.
    $node = $this->createNode();
    $this->setUpCurrentUser([], array_merge(['skip comment approval', 'access comments'], $this->userPermissions()));
    $this->createComment($node);

    // Create a user that is not allowed to view comments.
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
   * Test that a user can only see comments they're allowed to see in the list.
   *
   * - Any published comment
   * - Their own unpublished comment.
   */
  public function testUserCanViewOnlyOwnOrOtherPublishedComments() {
    $node = $this->createNode();
    // A user to create some other comments with.
    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
    // Unpublished comment.
    $this->createComment($node);
    // Published comment.
    $published_comment = $this->createComment($node, NULL, ['status' => 1]);

    // Create another user that can view published comments.
    // Users in Open Social can't view their own unpublished comments as they
    // may be unpublished as moderation action (and LU in Open Social have the
    // `bypass moderation` permission).
    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
    $this->createComment($node);

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

}
