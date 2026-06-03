<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\CurrentGroupProviderInterface;
use Drupal\social_group\Hooks\EntityFieldAccessHooks;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for EntityFieldAccessHooks (user status field access).
 *
 * @group social_group
 */
final class EntityFieldAccessHooksTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // AccessResult::allowed() with cache contexts calls Cache::mergeContexts(),
    // which requires the cache_contexts_manager service.
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * Tests that non-view operation returns neutral.
   */
  public function testNonViewOperationReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createUserStatusFieldDefinition();
    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('edit', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that non-status field returns neutral.
   */
  public function testNonStatusFieldReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('name');
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');

    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that the status field on a non-user entity returns neutral.
   */
  public function testNonUserTargetEntityReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');

    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that no current group returns neutral.
   */
  public function testNoCurrentGroupReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->method('getCurrentGroup')->willReturn(NULL);

    $field_definition = $this->createUserStatusFieldDefinition();
    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that group without administer members permission returns neutral.
   */
  public function testGroupWithoutAdministerMembersReturnsNeutral(): void {
    $group = $this->createMock(GroupInterface::class);
    $account = $this->createMock(AccountInterface::class);
    $group->method('hasPermission')
      ->with('administer members', $account)
      ->willReturn(FALSE);

    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->method('getCurrentGroup')->willReturn($group);

    $field_definition = $this->createUserStatusFieldDefinition();

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that group with administer members permission returns allowed.
   *
   * Items are NULL here because Views' column-level access check
   * (EntityField::access()) calls fieldAccess() without field items; this
   * is the call that decides whether the Status column is rendered at all.
   */
  public function testGroupWithAdministerMembersReturnsAllowedForColumnAccess(): void {
    $group = $this->createMock(GroupInterface::class);
    $account = $this->createMock(AccountInterface::class);
    $group->method('hasPermission')
      ->with('administer members', $account)
      ->willReturn(TRUE);
    $group->method('getCacheContexts')->willReturn([]);
    $group->method('getCacheTags')->willReturn([]);
    $group->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);

    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->method('getCurrentGroup')->willReturn($group);

    $field_definition = $this->createUserStatusFieldDefinition();

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isAllowed());
    assert($result instanceof AccessResult);
    $this->assertContains('route.group', $result->getCacheContexts());
    $this->assertContains('user.group_permissions', $result->getCacheContexts());
  }

  /**
   * Tests that group with administer members permission returns allowed.
   *
   * Per-row access check (items provided) — same result as the column-level
   * check.
   */
  public function testGroupWithAdministerMembersReturnsAllowedForRowAccess(): void {
    $group = $this->createMock(GroupInterface::class);
    $account = $this->createMock(AccountInterface::class);
    $group->method('hasPermission')
      ->with('administer members', $account)
      ->willReturn(TRUE);
    $group->method('getCacheContexts')->willReturn([]);
    $group->method('getCacheTags')->willReturn([]);
    $group->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);

    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->method('getCurrentGroup')->willReturn($group);

    $field_definition = $this->createUserStatusFieldDefinition();
    $items = $this->createMock(FieldItemListInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, $items);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Creates a mock field definition for the user.status field.
   */
  private function createUserStatusFieldDefinition(): FieldDefinitionInterface {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    return $field_definition;
  }

}
