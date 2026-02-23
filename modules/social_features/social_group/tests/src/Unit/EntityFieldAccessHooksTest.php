<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
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
   * Tests that non-view operation returns neutral.
   */
  public function testNonViewOperationReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    $field_definition->method('getName')->willReturn('status');

    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('edit', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that non-user entity type returns neutral.
   */
  public function testNonUserEntityTypeReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getName')->willReturn('status');

    $account = $this->createMock(AccountInterface::class);

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that non-status field returns neutral.
   */
  public function testNonStatusFieldReturnsNeutral(): void {
    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->expects($this->never())->method('getCurrentGroup');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    $field_definition->method('getName')->willReturn('name');

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

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    $field_definition->method('getName')->willReturn('status');

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

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    $field_definition->method('getName')->willReturn('status');

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that group with administer members permission returns allowed.
   */
  public function testGroupWithAdministerMembersReturnsAllowed(): void {
    $group = $this->createMock(GroupInterface::class);
    $account = $this->createMock(AccountInterface::class);
    $group->method('hasPermission')
      ->with('administer members', $account)
      ->willReturn(TRUE);

    $resolver = $this->createMock(CurrentGroupProviderInterface::class);
    $resolver->method('getCurrentGroup')->willReturn($group);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('user');
    $field_definition->method('getName')->willReturn('status');

    $hooks = new EntityFieldAccessHooks($resolver);
    $result = $hooks->entityFieldAccess('view', $field_definition, $account, NULL);

    $this->assertTrue($result->isAllowed());
    $this->assertContains('route.group', $result->getCacheContexts());
    $this->assertContains('user.group_permissions', $result->getCacheContexts());
  }

}
