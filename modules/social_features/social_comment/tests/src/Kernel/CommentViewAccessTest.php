<?php

namespace Drupal\Tests\social_comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\UserInterface;

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
  protected static $modules = [
    // For the comment functionality.
    'social_comment',
    'hux',
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
    'group_core_comments',
    'variationcache',
    'flexible_permissions',
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
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['filter', 'comment', 'social_comment']);

    $this->ensurePageCommentField();
    $this->storage = $this->entityTypeManager->getStorage('comment');
    $this->node = $this->createNode(['type' => 'page']);
  }

  /**
   * Ensures the page bundle has a comment field for access tests.
   */
  private function ensurePageCommentField(): void {
    if (NodeType::load('page') === NULL) {
      NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    }
    if (FieldStorageConfig::load('node.field_page_comments') === NULL) {
      FieldStorageConfig::create([
        'field_name' => 'field_page_comments',
        'entity_type' => 'node',
        'type' => 'comment',
        'settings' => ['comment_type' => 'comment'],
        'module' => 'comment',
      ])->save();
    }
    if (FieldConfig::load('node.page.field_page_comments') === NULL) {
      FieldConfig::create([
        'field_name' => 'field_page_comments',
        'entity_type' => 'node',
        'bundle' => 'page',
        'label' => 'Comments',
        'settings' => [
          'default_mode' => 1,
          'per_page' => 50,
        ],
      ])->save();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Until https://www.drupal.org/project/drupal/issues/3039955 is fixed.
   *
   * @phpstan-param bool $admin
   */
  protected function setUpCurrentUser(array $values = [], array $permissions = [], $admin = FALSE) : UserInterface {
    self::assertFalse($admin, "The current setUpCurrentUser workaround doesn't support admin users.");
    $user = $this->createUser($permissions, NULL, FALSE, $values);
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
   * Members cannot view comments when the parent comment field is Hidden.
   */
  public function testUserCanNotViewCommentWhenCommentFieldHidden(): void {
    $this->setUpCurrentUser([], ['access comments']);
    $comment = $this->createComment($this->node, [
      'field_name' => 'field_page_comments',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $this->node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $this->node->save();

    $member = $this->setUpCurrentUser([], ['access comments']);

    self::assertFalse($comment->access('view', $member));

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('cid', $comment->id())
      ->execute();
    self::assertCount(0, $visible_comments);
  }

  /**
   * Forbidden hidden-field view access varies per user account.
   */
  public function testHiddenCommentFieldAccessViewCachesPerUser(): void {
    $this->setUpCurrentUser([], ['access comments']);
    $comment = $this->createComment($this->node, [
      'field_name' => 'field_page_comments',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $this->node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $this->node->save();

    $member = $this->setUpCurrentUser([], ['access comments']);
    $access = \Drupal::service('social_comment.hidden_comment_field_access')
      ->accessView($comment, $member);

    self::assertTrue($access->isForbidden());
    self::assertInstanceOf(CacheableDependencyInterface::class, $access);
    self::assertContains('user', $access->getCacheContexts());
  }

  /**
   * Comment list query access includes user context for hidden fields.
   */
  public function testCommentQueryAccessIncludesUserCacheContextForHiddenFields(): void {
    $this->setUpCurrentUser([], ['access comments']);
    $comment = $this->createComment($this->node, [
      'field_name' => 'field_page_comments',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $this->node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $this->node->save();

    $member = $this->setUpCurrentUser([], ['access comments']);
    /** @var \Drupal\social_comment\Entity\Access\CommentQueryAccessHandler $handler */
    $handler = $this->entityTypeManager->getHandler('comment', 'query_access');
    $conditions = $handler->buildConditions('view', $member);

    self::assertContains('user', $conditions->getCacheContexts());
    self::assertCount(0, $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('cid', $comment->id())
      ->execute());
  }

  /**
   * Node owners can view comments when the parent comment field is Hidden.
   */
  public function testNodeOwnerCanViewCommentWhenCommentFieldHidden(): void {
    $owner = $this->setUpCurrentUser([], ['access comments', 'access content']);
    $node = $this->createNode([
      'type' => 'page',
      'uid' => $owner->id(),
    ]);
    $comment = $this->createComment($node, [
      'field_name' => 'field_page_comments',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $node->save();

    self::assertTrue($comment->access('view', $owner));

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('cid', $comment->id())
      ->execute();
    self::assertCount(1, $visible_comments);
  }

  /**
   * Hidden comment access follows node ownership after reassignment.
   */
  public function testNodeOwnerAccessChangesWhenOwnershipTransferred(): void {
    $original_owner = $this->setUpCurrentUser([], ['access comments', 'access content']);
    $node = $this->createNode([
      'type' => 'page',
      'uid' => $original_owner->id(),
    ]);
    $comment = $this->createComment($node, [
      'field_name' => 'field_page_comments',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $node->save();

    self::assertTrue($comment->access('view', $original_owner));
    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('cid', $comment->id())
      ->execute();
    self::assertCount(1, $visible_comments);

    $new_owner = $this->setUpCurrentUser([], ['access comments', 'access content']);
    $node->setOwnerId((int) $new_owner->id());
    $node->save();
    $comment = $this->storage->load($comment->id());
    self::assertInstanceOf(CommentInterface::class, $comment);

    self::assertFalse($comment->access('view', $original_owner));
    self::assertTrue($comment->access('view', $new_owner));

    $visible_comments = $this->storage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('cid', $comment->id())
      ->execute();
    self::assertCount(1, $visible_comments);
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
