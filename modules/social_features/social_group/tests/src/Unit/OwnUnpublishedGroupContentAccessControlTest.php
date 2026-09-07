<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\Plugin\Group\RelationHandler\OwnUnpublishedGroupContentAccessControl;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the decorator that lets an owner see their own unpublished content.
 *
 * The decorator's whole job is to turn exactly one "forbidden" into a
 * "neutral". Every condition that narrows it is therefore pinned by its own
 * test, with the mirror case proving the delegation still happens.
 *
 * @coversDefaultClass \Drupal\social_group\Plugin\Group\RelationHandler\OwnUnpublishedGroupContentAccessControl
 *
 * @group social_group
 */
class OwnUnpublishedGroupContentAccessControlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // AccessResult's cache metadata validates its contexts against the
    // container, so a unit test needs a cache contexts manager in place.
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * The owner's own unpublished content loses Group's veto.
   *
   * @covers ::entityAccess
   */
  public function testOwnUnpublishedForbiddenBecomesNeutral(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(5);
    $handler = $this->handler(AccessResult::forbidden(), $node, 'view', $account);

    $result = $handler->entityAccess($node, 'view', $account, TRUE);

    assert($result instanceof AccessResultInterface);
    $this->assertTrue($result->isNeutral());
    $this->assertFalse($result->isForbidden());
  }

  /**
   * The neutral result still varies per user and follows the entity.
   *
   * The decision reads the entity's owner and publication status and compares
   * them to the requesting account, so both axes have to survive in the cache
   * metadata - alongside whatever the decorated handler had collected.
   *
   * @covers ::entityAccess
   */
  public function testNeutralResultKeepsCacheability(): void {
    $node = $this->node(FALSE, 5, ['node:11'], 600);
    $account = $this->account(5);
    $parent_result = AccessResult::forbidden()->addCacheTags(['group_content:7']);
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $result = $handler->entityAccess($node, 'view', $account, TRUE);

    assert($result instanceof AccessResult);
    $this->assertEqualsCanonicalizing(['group_content:7', 'node:11'], $result->getCacheTags());
    $this->assertEqualsCanonicalizing(['user'], $result->getCacheContexts());
    $this->assertSame(600, $result->getCacheMaxAge());
  }

  /**
   * Without $return_as_object the neutral result reads as "no access".
   *
   * Neutral is not "allowed": the decorator only removes Group's veto and
   * leaves the granting to the node access handlers.
   *
   * This one pins the return-value contract rather than the decision - a
   * forbidden parent also reads as FALSE - so the discriminating proof of the
   * neutral path lives in the three tests that inspect the result object.
   *
   * @covers ::entityAccess
   */
  public function testNeutralResultReturnedAsBooleanIsFalse(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(5);
    $handler = $this->handler(AccessResult::forbidden(), $node, 'view', $account);

    $this->assertFalse($handler->entityAccess($node, 'view', $account));
  }

  /**
   * An operation other than "view" is none of this decorator's business.
   *
   * @covers ::entityAccess
   */
  public function testUpdateOperationIsDelegated(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(5);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $node, 'update', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'update', $account, TRUE));
  }

  /**
   * An entity type with no publication status is delegated.
   *
   * @covers ::entityAccess
   */
  public function testEntityWithoutPublishedInterfaceIsDelegated(): void {
    $entity = $this->createMock(EntityInterface::class);
    $account = $this->account(5);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $entity, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($entity, 'view', $account, TRUE));
  }

  /**
   * Published content is delegated - Group never vetoed it over publication.
   *
   * @covers ::entityAccess
   */
  public function testPublishedEntityIsDelegated(): void {
    $node = $this->node(TRUE, 5);
    $account = $this->account(5);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'view', $account, TRUE));
  }

  /**
   * An entity type with no owner has nobody to make this exception for.
   *
   * @covers ::entityAccess
   */
  public function testEntityWithoutOwnerInterfaceIsDelegated(): void {
    $entity = $this->createMock(BlockContentInterface::class);
    $entity->method('isPublished')->willReturn(FALSE);
    $account = $this->account(5);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $entity, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($entity, 'view', $account, TRUE));
  }

  /**
   * Anonymous never inherits the exception, even on uid 0 owned content.
   *
   * "Own" content of the anonymous user is not an ownership claim; lifting the
   * veto there would hand unpublished group content to every visitor.
   *
   * @covers ::entityAccess
   */
  public function testAnonymousOwnerIsDelegated(): void {
    $node = $this->node(FALSE, 0);
    $account = $this->account(0, FALSE);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'view', $account, TRUE));
  }

  /**
   * Somebody else's unpublished content stays forbidden.
   *
   * This is the negative control of the whole decorator: without it the change
   * would open every unpublished item in a group to every member.
   *
   * @covers ::entityAccess
   */
  public function testNonOwnerIsDelegated(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(6);
    $parent_result = AccessResult::forbidden();
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'view', $account, TRUE));
  }

  /**
   * An integer owner id and a string account id are the same person.
   *
   * Entity keys and session ids are not consistently typed, so the comparison
   * is made on strings rather than with the strict check Group itself uses.
   *
   * @covers ::entityAccess
   */
  public function testOwnerIdComparisonIsTypeInsensitive(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account('5');
    $handler = $this->handler(AccessResult::forbidden(), $node, 'view', $account);

    $result = $handler->entityAccess($node, 'view', $account, TRUE);

    assert($result instanceof AccessResultInterface);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * An allowed result is handed back untouched.
   *
   * @covers ::entityAccess
   */
  public function testAllowedParentResultIsDelegated(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(5);
    $parent_result = AccessResult::allowed();
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'view', $account, TRUE));
  }

  /**
   * A parent that already abstains is handed back untouched.
   *
   * @covers ::entityAccess
   */
  public function testNeutralParentResultIsDelegated(): void {
    $node = $this->node(FALSE, 5);
    $account = $this->account(5);
    $parent_result = AccessResult::neutral();
    $handler = $this->handler($parent_result, $node, 'view', $account);

    $this->assertSame($parent_result, $handler->entityAccess($node, 'view', $account, TRUE));
  }

  /**
   * Delegation without $return_as_object returns the parent's boolean.
   *
   * @covers ::entityAccess
   */
  public function testDelegatedResultReturnedAsBoolean(): void {
    $node = $this->node(TRUE, 5);
    $account = $this->account(5);
    $handler = $this->handler(AccessResult::allowed(), $node, 'view', $account);

    $this->assertTrue($handler->entityAccess($node, 'view', $account));
  }

  /**
   * A handler whose parent was never assigned refuses to guess.
   *
   * @covers ::entityAccess
   */
  public function testMissingParentThrows(): void {
    $handler = new OwnUnpublishedGroupContentAccessControl(
      $this->createMock(AccessControlInterface::class)
    );
    $property = new \ReflectionProperty($handler, 'parent');
    $property->setAccessible(TRUE);
    $property->setValue($handler, NULL);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    $handler->entityAccess($this->createMock(EntityInterface::class), 'view', $this->account(5), TRUE);
  }

  /**
   * Builds the decorator over a parent returning a fixed result.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $parent_result
   *   The result the decorated handler returns.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the parent is expected to be asked about.
   * @param string $operation
   *   The operation the parent is expected to be asked about.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account the parent is expected to be asked about.
   *
   * @return \Drupal\social_group\Plugin\Group\RelationHandler\OwnUnpublishedGroupContentAccessControl
   *   The decorator under test.
   */
  protected function handler(AccessResultInterface $parent_result, EntityInterface $entity, string $operation, AccountInterface $account): OwnUnpublishedGroupContentAccessControl {
    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($entity, $operation, $account, TRUE)
      ->willReturn($parent_result);

    return new OwnUnpublishedGroupContentAccessControl($parent);
  }

  /**
   * Builds a node mock.
   *
   * @param bool $published
   *   Whether the node is published.
   * @param int|string $owner_id
   *   The node's owner id.
   * @param string[] $cache_tags
   *   The node's cache tags.
   * @param int $max_age
   *   The node's cache max age.
   *
   * @return \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The node mock.
   */
  protected function node(bool $published, $owner_id, array $cache_tags = [], int $max_age = Cache::PERMANENT) {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn($published);
    $node->method('getOwnerId')->willReturn($owner_id);
    $node->method('getCacheTags')->willReturn($cache_tags);
    $node->method('getCacheContexts')->willReturn([]);
    $node->method('getCacheMaxAge')->willReturn($max_age);

    return $node;
  }

  /**
   * Builds an account mock.
   *
   * @param int|string $id
   *   The account id.
   * @param bool $authenticated
   *   Whether the account is authenticated.
   *
   * @return \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The account mock.
   */
  protected function account($id, bool $authenticated = TRUE) {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn($id);
    $account->method('isAuthenticated')->willReturn($authenticated);

    return $account;
  }

}
