<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit\GraphQL;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_group_flexible_group\GraphQL\FlexibleGroupHubTypeMapper;
use Drupal\social_group_flexible_group\GraphQL\FlexibleGroupProfileAffiliatableTypeMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests flexible group GraphQL type mappers.
 *
 * @group social_group_flexible_group
 */
class FlexibleGroupProfileAffiliatableTypeMapperTest extends UnitTestCase {

  /**
   * Flexible groups map to Group; other bundles do not.
   */
  public function testHubMapper(): void {
    $mapper = new FlexibleGroupHubTypeMapper();

    $this->assertSame('Group', $mapper->resolve($this->mockGroup('flexible_group')));
    $this->assertNull($mapper->resolve($this->mockGroup('organization')));
    $this->assertNull($mapper->resolve(new \stdClass()));
  }

  /**
   * Flexible group affiliations map to UserGroupAffiliation.
   */
  public function testProfileAffiliatableMapper(): void {
    $mapper = new FlexibleGroupProfileAffiliatableTypeMapper();

    $this->assertSame('UserGroupAffiliation', $mapper->resolve($this->mockMembership('flexible_group')));
    $this->assertNull($mapper->resolve($this->mockMembership('organization')));
    $this->assertNull($mapper->resolve(new \stdClass()));
  }

  /**
   * Creates a group mock for the given bundle.
   */
  private function mockGroup(string $bundle): GroupInterface {
    $group = $this->createMock(GroupInterface::class);
    $group->method('bundle')->willReturn($bundle);
    return $group;
  }

  /**
   * Creates a membership for the given group type.
   */
  private function mockMembership(string $bundle): GroupRelationshipInterface {
    $membership = $this->createMock(GroupRelationshipInterface::class);
    $membership->method('bundle')->willReturn($bundle . '-group_membership');
    return $membership;
  }

}
