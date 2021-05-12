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
 * Tests the comment field on the Query type.
 *
 * @group social_graphql
 * @group social_comment
 */
class QueryCommentTest extends SocialGraphQLTestBase {

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
   * Test that the fields provided by the module can be queried.
   */
  public function testSupportsFieldsIncludedInModule() : void {
    // Create a node to comment on.
    $node = $this->createNode();

    // Set up a user that can create a published comment and view it.
    // The default publishing status for comments looks at the current user
    // rather than the comment author.
    $user = $this->setUpCurrentUser([], array_merge(['skip comment approval', 'access comments'], $this->userPermissions()));

    // We expect our bodyHtml to come out processed. This includes a linebreak
    // that seems to be added by the renderer for funsies.
    $raw_comment_body = "This is a link test: https://social.localhost";
    $html_comment_body = '<div><p>This is a link test: <a href="https://social.localhost">https://social.localhost</a></p>
</div>';
    $comment = $this->createComment(
      $node,
      NULL,
      ['field_comment_body' => $raw_comment_body]
    );

    $query = '
      query ($id: ID!) {
        comment(id: $id) {
          id
          author {
            id
          }
          bodyHtml
          created {
            timestamp
          }
        }
      }
    ';

    $this->assertResults(
      $query,
      ['id' => $comment->uuid()],
      [
        'comment' => [
          'id' => $comment->uuid(),
          'author' => [
            'id' => $user->uuid(),
          ],
          'bodyHtml' => $html_comment_body,
          'created' => [
            'timestamp' => $comment->getCreatedTime(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($node)
        ->addCacheableDependency($user)
        ->addCacheableDependency($comment)
        ->addCacheTags(['config:filter.format.plain_text', 'config:filter.settings'])
        // @todo It's unclear why this cache context is added.
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that it respects the access comments permission.
   */
  public function testRequiresAccessCommentsPermission() {
    // Create a published comment on a node.
    $node = $this->createNode();
    $this->setUpCurrentUser([], array_merge(['skip comment approval', 'access comments'], $this->userPermissions()));
    $comment = $this->createComment($node);

    // Create a user that is not allowed to view comments.
    $this->setUpCurrentUser([], $this->userPermissions());

    $this->assertResults('
        query ($id: ID!) {
          comment(id: $id) {
            id
          }
        }
      ',
      ['id' => $comment->uuid()],
      ['comment' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($comment)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a user can not view their own unpublished comment.
   *
   * This mirrors the functionality of the distribution at the time of writing
   * the test.
   */
  public function testUserCanNotViewOwnUnpublishedComment() {
    // Create an unpublished comment on a node.
    $node = $this->createNode();
    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
    $comment = $this->createComment($node);

    $this->assertResults('
        query ($id: ID!) {
          comment(id: $id) {
            id
          }
        }
      ',
      ['id' => $comment->uuid()],
      ['comment' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($comment)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that a user can not view another person's unpublished comment.
   */
  public function testUserCanNotViewOtherUnpublishedComment() {
    // Create an unpublished comment on a node.
    $node = $this->createNode();
    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
    $comment = $this->createComment($node);

    // Create another user to try and view the comment.
    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));

    $this->assertResults('
        query ($id: ID!) {
          comment(id: $id) {
            id
          }
        }
      ',
      ['id' => $comment->uuid()],
      ['comment' => NULL],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($comment)
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
