<?php

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the comments endpoint added to the Open Social schema by this module.
 *
 * This uses the GraphQLTestBase which extends KernelTestBase since this class
 * is interested in testing the implementation of the GraphQL schema that's a
 * part of this module. We're not interested in the HTTP functionality since
 * that is covered by the graphql module itself. Thus BrowserTestBase is not
 * needed.
 *
 * @group social_graphql
 */
class GraphQLCommentsEndpointTest extends SocialGraphQLTestBase {

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
  public function testPaginatedQueryComments(): void {
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
   * Test that a specific comment and its contents can be fetched by uuid.
   */
  public function testCanQueryOwnPublishedComment() : void {
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
            displayName
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
            'displayName' => $user->getDisplayName(),
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
