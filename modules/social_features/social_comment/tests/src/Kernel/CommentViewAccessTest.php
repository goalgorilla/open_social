<?php

namespace Drupal\Tests\social_comment\Kernel;

use Drupal\comment\CommentInterface;
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
   * {@inheritdoc}
   *
   * Until https://www.drupal.org/project/drupal/issues/3039955 is fixed.
   */
  protected function setUpCurrentUser(array $values = [], array $permissions = [], $admin = FALSE) {
    self::assertFalse($admin, "The current setUpCurrentUser workaround doesn't support admin users.");
    $user = $this->createUser($values, $permissions);
    $this->setCurrentUser($user);
    return $user;
  }

  /**
   * Test that a user can view only published comment.
   */
  public function testUserCanViewOnlyPublishedComment() {
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node);
    $this->createComment($this->node, ['status' => 1]);
    $this->createComment($this->node, ['status' => 1]);

    // Create another user to try and view the comment.
    $this->setUpCurrentUser([], ['access comments']);

    $all_comments = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();

    self::assertCount(3, $all_comments);

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();

    self::assertCount(2, $visible_comments);
  }

  /**
   * Test that a user can not view comment without permission.
   */
  public function testUserCanNotViewCommentWithoutPermission() {
    $this->setUpCurrentUser([], ['access comments']);

    $this->createComment($this->node);
    $this->createComment($this->node, ['status' => 1]);

    // Create another user to try and view the comment.
    $this->setUpCurrentUser();

    $all_comments = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();

    self::assertCount(2, $all_comments);

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();

    self::assertEmpty($visible_comments);
  }

  /**
   * Create and save a comment entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the comment is made on.
   * @param mixed[] $values
   *   An optional array of values to pass to Comment::create.
   *
   * @return \Drupal\comment\CommentInterface
   *   The created comment.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createComment(EntityInterface $entity, array $values = []): CommentInterface {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->storage->create(
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
