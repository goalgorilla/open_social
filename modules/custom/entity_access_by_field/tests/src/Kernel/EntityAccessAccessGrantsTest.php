<?php

namespace Drupal\Tests\entity_access_by_field\Kernel;

/**
 * Tests the access records that are set for group nodes.
 *
 * @group entity_access_by_field
 */
class EntityAccessAccessGrantsTest extends EntityAccessNodeAccessTestBase {

  /**
   * Tests that groups with no membership for the user are excluded.
   */
  public function testGrantsAlter() :void {
    // Create a grants array.
    // We focus on the node types, which is what
    // entity_access_by_field_node_grants_alter() attempts to influence.
    $grants = [
      'gnode:a' => [1, 2],
      'gnode:b' => [1, 2],
    ];

    // Verify that when a member is part of both groups, nothing will be
    // altered.
    $altered_grants_groups12 = $grants;
    entity_access_by_field_node_grants_alter($altered_grants_groups12, $this->account, 'view');
    $this->assertEquals($grants, $altered_grants_groups12);

    drupal_static_reset('entity_access_by_field_node_grants_alter');
    drupal_static_reset('getAllGroupsForUser');
    // Verify that when a member is removed from one group, it is altered as
    // such.
    $this->group1->removeMember($this->account);
    $altered_grants_groups2 = $grants;
    entity_access_by_field_node_grants_alter($altered_grants_groups2, $this->account, 'view');

    $expected_result_groups2 = [
      'gnode:a' => [1 => 2],
      'gnode:b' => [1 => 2],
    ];
    $this->assertEquals($expected_result_groups2, $altered_grants_groups2);

    drupal_static_reset('entity_access_by_field_node_grants_alter');
    drupal_static_reset('getAllGroupsForUser');
    // Verify that when a member is not a member of any group, it is altered as
    // such.
    $this->group2->removeMember($this->account);
    $altered_grants_groups_none = $grants;
    entity_access_by_field_node_grants_alter($altered_grants_groups_none, $this->account, 'view');

    $expected_result_groups_none = [
      'gnode:a' => [],
      'gnode:b' => [],
    ];
    $this->assertEquals($expected_result_groups_none, $altered_grants_groups_none);
  }

}
