<?php

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\social_group\Hooks\SocialGroupOperationHooks;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for SocialGroupOperationHooks class.
 *
 * @group social_group
 */
class SocialGroupOperationHooksTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\social_group\Hooks\SocialGroupOperationHooks
   */
  protected SocialGroupOperationHooks $socialGroupOperationHooks;

  /**
   * Entity class.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * Set up the test case.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->socialGroupOperationHooks = new SocialGroupOperationHooks();

    $this->entity = $this->getMockBuilder('\Drupal\group\Entity\Group')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('group');
  }

  /**
   * Test operations from group entity with nodes.
   */
  public function testEntityOperationWithNodesValue(): void {
    $operations = [
      'delete' => $this->randomMachineName(),
      'nodes' => $this->randomMachineName(),
    ];

    $this->socialGroupOperationHooks->removeUnusedOperations($operations, $this->entity);

    $this->assertArrayHasKey('delete', $operations);
    $this->assertArrayNotHasKey('nodes', $operations);
  }

  /**
   * Test operations from group entity with media.
   */
  public function testEntityOperationWithMediaValue(): void {
    $operations = [
      'delete' => $this->randomMachineName(),
      'media' => $this->randomMachineName(),
    ];

    $this->socialGroupOperationHooks->removeUnusedOperations($operations, $this->entity);

    $this->assertArrayHasKey('delete', $operations);
    $this->assertArrayNotHasKey('media', $operations);
  }

  /**
   * Test operations from group entity with nodes and media.
   */
  public function testEntityOperationWithNodesAndMediaValue(): void {
    $operations = [
      'delete' => $this->randomMachineName(),
      'nodes' => $this->randomMachineName(),
      'media' => $this->randomMachineName(),
    ];

    $this->socialGroupOperationHooks->removeUnusedOperations($operations, $this->entity);

    $this->assertArrayHasKey('delete', $operations);
    $this->assertArrayNotHasKey('media', $operations);
    $this->assertArrayNotHasKey('nodes', $operations);
  }

  /**
   * Test operations from group entity without nodes and media.
   */
  public function testEntityOperationWithoutNodesAndMediaValue(): void {
    $operations = [
      'translate' => $this->randomMachineName(),
      'delete' => $this->randomMachineName(),
    ];

    $this->socialGroupOperationHooks->removeUnusedOperations($operations, $this->entity);

    $this->assertArrayHasKey('translate', $operations);
    $this->assertArrayHasKey('delete', $operations);
  }

  /**
   * Test operations from group entity without nodes and media.
   */
  public function testEntityOperationWithNonGroupEntity(): void {
    $entity = $this->getMockBuilder('\Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');

    $operations = [
      'translate' => $this->randomMachineName(),
      'delete' => $this->randomMachineName(),
      'nodes' => $this->randomMachineName(),
      'media' => $this->randomMachineName(),
    ];

    $this->socialGroupOperationHooks->removeUnusedOperations($operations, $entity);

    $this->assertArrayHasKey('translate', $operations);
    $this->assertArrayHasKey('delete', $operations);
    $this->assertArrayHasKey('nodes', $operations);
    $this->assertArrayHasKey('media', $operations);
  }

}
