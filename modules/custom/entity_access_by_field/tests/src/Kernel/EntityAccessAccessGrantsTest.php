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
    // @todo Rewrite test with entity query access check.
  }

}
