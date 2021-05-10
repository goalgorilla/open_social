<?php

namespace Drupal\Tests\social_comment\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests comment view level access.
 *
 * @group social_comment
 */
class CommentViewAccessTest extends EntityKernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // For the comment functionality.
    'social_comment',
    'comment',
    // For checking access to comments.
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
   * The comment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $storage;

  /**
   * Node entity to use in this test.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['filter', 'comment', 'social_comment']);

    $this->storage = $this->entityTypeManager->getStorage('comment');
    $this->node = $this->createNode();
  }

  /**
   * Test that a user can not view their own unpublished comment.
   *
   * This mirrors the functionality of the distribution at the time of writing
   * the test.
   */
  public function testUserCanNotViewOwnUnpublishedComment() {
    // Create an unpublished comment on a node.
    $user = $this->createUser([], ['access comments']);
    $this->setCurrentUser($user);
    $this->createComment($this->node, $user, ['status' => 0]);

    $this->assertEmpty($this->getCommentIds($this->node, $user));
  }

  /**
   * Test that a user can view their own published comment.
   *
   * This mirrors the functionality of the distribution at the time of writing
   * the test.
   */
  public function testUserCanViewOwnPublishedComment() {
    // Create an unpublished comment on a node.
    $user = $this->createUser([], ['access comments']);
    $this->setCurrentUser($user);
    $this->createComment($this->node, $user, ['status' => 1]);

    $this->assertNotEmpty($this->getCommentIds($this->node, $user));
  }

  /**
   * Test that a user can not view another person's unpublished comment.
   */
  public function testUserCanNotViewOtherUnpublishedComment() {
    // Create an unpublished comment on a node.
    $first_user = $this->createUser([], ['access comments']);
    $this->createComment($this->node, $first_user, ['status' => 0]);

    // Create another user to try and view the comment.
    $second_user = $this->createUser([], ['access comments']);
    $this->setCurrentUser($second_user);

    $this->assertEmpty($this->getCommentIds($this->node, $first_user));
  }

  /**
   * Test that a user can view another person's published comment.
   */
  public function testUserCanViewOtherPublishedComment() {
    // Create an unpublished comment on a node.
    $first_user = $this->createUser([], ['access comments']);
    $this->createComment($this->node, $first_user, ['status' => 1]);

    // Create another user to try and view the comment.
    $second_user = $this->createUser([], ['access comments']);
    $this->setCurrentUser($second_user);

    $this->assertNotEmpty($this->getCommentIds($this->node, $first_user));
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createComment(EntityInterface $entity, ?AccountInterface $user = NULL, array $values = []): void {
    if ($user !== NULL) {
      $values += ['uid' => $user->id()];
    }

    $this->storage->create(
      $values +
      [
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'comment_type' => 'comment',
        'field_name' => 'comments',
      ]
    )->save();
  }

  /**
   * Get a list of comment IDs whose user have access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the comment is made on.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to create the comment as.
   *
   * @return array
   *   An array of comment IDs.
   */
  private function getCommentIds(EntityInterface $entity, AccountInterface $user): array {
    return $this->storage
      ->getQuery()
      ->currentRevision()
      ->accessCheck()
      ->condition('entity_id', $entity->id())
      ->condition('comment_type', 'comment')
      ->condition('uid', $user->id())
      ->execute();
  }

}
