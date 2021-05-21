<?php

namespace Drupal\Tests\social_comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityInterface;
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
    // For its `use_entity_access_api` setting.
    'social_core',
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

    // Manually enable query_access checks, until `use_entity_access_api` is no
    // longer a setting. Installing all the `social_core` config doesn't work at
    // the time of writing. This must happen before the comment entity is
    // installed or its handlers will be incorrect.
    $this->config('social_core.settings')
      ->set('use_entity_access_api', TRUE)
      ->save(TRUE);

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
   * Test that a user can not view comment without permission.
   *
   * Regardless of published status.
   */
  public function testUserCanNotViewCommentWithoutPermission() : void {
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node, ['status' => CommentInterface::NOT_PUBLISHED]);
    $this->createComment($this->node, ['status' => CommentInterface::PUBLISHED]);

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
    self::assertCount(0, $visible_comments);
  }

  /**
   * Test that a user can't view their own unpublished comments.
   */
  public function testUserCanNotViewOwnUnpublishedComment() : void {
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node, ['status' => CommentInterface::NOT_PUBLISHED]);

    $all_comments = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();
    self::assertCount(1, $all_comments);

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();

    self::assertCount(0, $visible_comments);
  }

  /**
   * Test that a user can't view other people's unpublished comments.
   */
  public function testUserCanNotViewOtherUnpublishedComment() : void {
    // Create a published comment.
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node, ['status' => CommentInterface::NOT_PUBLISHED]);

    // Create another user to view the comment.
    $this->setUpCurrentUser([], ['access comments']);

    $all_comments = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();
    self::assertCount(1, $all_comments);

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_id', $this->node->id())
      ->condition('comment_type', 'comment')
      ->execute();
    self::assertCount(0, $visible_comments);
  }

  /**
   * Test that a user can view everyone's published comments.
   */
  public function testUserCanViewOnlyPublishedComment() {
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node, ['status' => CommentInterface::PUBLISHED]);

    // Create another user to try and view the comment.
    $this->setUpCurrentUser([], ['access comments']);
    $this->createComment($this->node, ['status' => CommentInterface::PUBLISHED]);

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
    self::assertCount(2, $visible_comments);
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
