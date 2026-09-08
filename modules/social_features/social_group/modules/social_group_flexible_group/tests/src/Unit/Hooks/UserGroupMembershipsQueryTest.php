<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit\Hooks;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_group_flexible_group\Hooks\UserGroupMembershipsQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Tests UserGroupMembershipsQuery visibility alter behavior.
 *
 * @coversDefaultClass \Drupal\social_group_flexible_group\Hooks\UserGroupMembershipsQuery
 * @group social_group_flexible_group
 */
final class UserGroupMembershipsQueryTest extends UnitTestCase {

  /**
   * Ensures missing gid resolution fails closed.
   */
  public function testFailsClosedWhenGidFieldIsMissing(): void {
    $query = $this->createMock(SelectInterface::class);
    $query->method('getTables')->willReturn([
      'base' => ['table' => 'groups_field_data'],
    ]);
    $query->expects($this->once())->method('alwaysFalse');

    $alter = $this->createAlter();
    $alter->alterUserGroupMembershipsVisibleGroups($query);
  }

  /**
   * Ensures non-select queries are not altered.
   */
  public function testSkipsNonSelectQueries(): void {
    $query = $this->createMock(AlterableInterface::class);

    $alter = $this->createAlter();
    $alter->alterUserGroupMembershipsVisibleGroups($query);

    $this->addToAssertionCount(1);
  }

  /**
   * Builds the alter hook under test.
   */
  private function createAlter(?Connection $connection = NULL): UserGroupMembershipsQuery {
    return new UserGroupMembershipsQuery(
      $connection ?? $this->createMock(Connection::class),
      $this->createMock(AccountInterface::class),
    );
  }

}
