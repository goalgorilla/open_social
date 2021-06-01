<?php

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the topics field on the Query type.
 *
 * @group social_graphql
 * @group social_topic
 */
class QueryTopicsTest extends SocialGraphQLTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // For the topic functionality.
    'social_topic',
    'entity',
    // For the topic author and viewer.
    'social_user',
    'oauth2_server',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures topics for nodes.
    'node',
    // The default topic config contains a body text field.
    'field',
    'text',
    'filter',

    'file',
    'image',
    // For the comment functionality.
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
//    Error: Call to a member function getConfigDependencyName() on null
    'social_core',
    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',
//    views.view.latest_topics
    'group',
  ];


  /**
   * The list of comments.
   *
   * @var \Drupal\comment\CommentInterface[]
   */
  private $topics = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
//    $this->installEntitySchema('filter_format');

    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['node', 'social_core', 'social_topic']);

  }

  /**
   * Test that platform topics can be fetched using platform pagination.
   */
  public function testSupportsRelayPagination(): void {
    // Act as a user that can create and view published topics and contents.
    $this->setUpCurrentUser([], $this->userPermissions());

    // Create a bunch of test topics for pagination testing.
    $topics = [];

    for ($i = 0; $i < 10; ++$i) {
      $node = Node::create([
        'title' => $this->randomMachineName(8),
        'type' => 'topic',
      ]);
      $node->save();

      $topics[] = $node;
    }
//    print_r(\Drupal::entityTypeManager()->getStorage('node')->getQuery()->condition('type', 'topic')->execute());
//    print_r($topics);

    $topic_uuids = array_map(
      static fn ($topic) => $topic->uuid(),
      $topics
    );

//    print_r($topic_uuids);
    $this->assertEndpointSupportsPagination(
      'topics',
      $topic_uuids
    );
  }
//
//  /**
//   * Test that the topics endpoint respects the access topics permission.
//   */
//  public function testUserRequiresAccessCommentsPermission() {
//    // Create a published topic on a node.
//    $node = $this->createNode();
//    $this->setUpCurrentUser([], array_merge(['skip topic approval', 'access comments'], $this->userPermissions()));
//    $this->createComment($node);
//
//    // Create a user that is not allowed to view comments.
//    $this->setUpCurrentUser([], $this->userPermissions());
//
//    $this->assertResults('
//        query {
//          comments(first: 1) {
//            nodes {
//              id
//            }
//          }
//        }
//      ',
//      [],
//      [
//        'comments' => [
//          'nodes' => [],
//        ],
//      ],
//      $this->defaultCacheMetaData()
//        ->setCacheMaxAge(0)
//        ->addCacheContexts(['languages:language_interface'])
//    );
//  }
//
//  /**
//   * Test that a user can only see topics they're allowed to see in the list.
//   *
//   * - Any published comment
//   * - Their own unpublished comment.
//   */
//  public function testUserCanViewOnlyOwnOrOtherPublishedComments() {
//    $node = $this->createNode();
//    // A user to create some other topics with.
//    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
//    // Unpublished comment.
//    $this->createComment($node);
//    // Published comment.
//    $published_comment = $this->createComment($node, NULL, ['status' => 1]);
//
//    // Create another user that can view published comments.
//    $this->setUpCurrentUser([], array_merge(['access comments'], $this->userPermissions()));
//    $own_unpublished_comment = $this->createComment($node);
//
//    $this->assertResults('
//        query {
//          comments(last: 3) {
//            pageInfo {
//              hasNextPage
//              hasPreviousPage
//            }
//            nodes {
//              id
//            }
//          }
//        }
//      ',
//      [],
//      [
//        'comments' => [
//          'pageInfo' => [
//            'hasNextPage' => FALSE,
//            'hasPreviousPage' => FALSE,
//          ],
//          'nodes' => [
//            ['id' => $published_comment->uuid()],
//          ],
//        ],
//      ],
//      $this->defaultCacheMetaData()
//        ->setCacheMaxAge(0)
//        ->addCacheableDependency($published_comment)
//        ->addCacheContexts(['languages:language_interface'])
//    );
//  }
//
//  /**
//   * Create the topic entity.
//   *
//   * @param \Drupal\Core\Entity\EntityInterface $entity
//   *   The entity the topic is made on.
//   * @param \Drupal\Core\Session\AccountInterface|null $user
//   *   An optional user to create the topic as.
//   * @param mixed[] $values
//   *   An optional array of values to pass to Comment::create.
//   *
//   * @return \Drupal\comment\CommentInterface
//   *   Created topic entity.
//   */
//  private function createComment(EntityInterface $entity, ?AccountInterface $user = NULL, array $values = []) {
//    if ($user !== NULL) {
//      $values += ['uid' => $user->id()];
//    }
//
//    /** @var \Drupal\comment\CommentInterface $comment */
//    $comment = Comment::create(
//      $values +
//      [
//        'entity_id' => $entity->id(),
//        'entity_type' => $entity->getEntityTypeId(),
//        'comment_type' => 'comment',
//        'field_name' => 'comments',
//      ]
//    );
//
//    $comment->save();
//
//    return $comment;
//  }

}
