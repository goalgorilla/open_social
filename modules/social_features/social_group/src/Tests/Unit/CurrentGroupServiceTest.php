<?php

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\CurrentGroupService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for CurrentGroupService.
 *
 * @group social_group
 */
class CurrentGroupServiceTest extends UnitTestCase {

  /**
   * The mocked EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * The mocked ContextProviderInterface.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private ContextProviderInterface|MockObject $groupRouteContext;

  /**
   * The service under test.
   *
   * @var \Drupal\social_group\CurrentGroupService
   */
  private CurrentGroupService $currentGroupService;

  /**
   * Set up the test case.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->groupRouteContext = $this->createMock(ContextProviderInterface::class);

    $this->currentGroupService = new CurrentGroupService(
      $this->entityTypeManager,
      $this->groupRouteContext
    );
  }

  /**
   * Helper function to create a mock context.
   *
   * @param mixed $value
   *   The value returned by typeDataInterface.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The updated mock context.
   */
  private function createContextWithValue(mixed $value): ContextInterface {
    $typeDataInterface = $this->createMock(TypedDataInterface::class);
    $typeDataInterface->expects($this->once())
      ->method('getValue')
      ->willReturn($value);

    $context = $this->createMock(ContextInterface::class);
    $context->expects($this->once())
      ->method('getContextData')
      ->willReturn($typeDataInterface);

    return $context;
  }

  /**
   * Test that fromRunTimeContexts() returns NULL when no group context is set.
   */
  public function testFromRunTimeContextsNoGroupContext(): void {
    $this->groupRouteContext
      ->expects($this->once())
      ->method('getRuntimeContexts')
      ->willReturn([]);

    $group = $this->currentGroupService->fromRunTimeContexts();

    $this->assertNull($group, 'No group context should return NULL.');
  }

  /**
   * Test that fromRunTimeContexts() returns a GroupInterface when available.
   */
  public function testFromRunTimeContextsWithGroup(): void {
    $group = $this->createMock(GroupInterface::class);
    $context = $this->createContextWithValue($group);

    $this->groupRouteContext
      ->expects($this->once())
      ->method('getRuntimeContexts')
      ->willReturn(['group' => $context]);

    $result = $this->currentGroupService->fromRunTimeContexts();

    $this->assertSame($group, $result, 'Group should be returned when found in context.');
  }

  /**
   * Test that fromRunTimeContexts() loads a group by ID.
   */
  public function testFromRunTimeContextsWithGroupId(): void {
    $groupId = 1;
    $group = $this->createMock(GroupInterface::class);

    $context = $this->createContextWithValue($groupId);

    $this->groupRouteContext
      ->expects($this->once())
      ->method('getRuntimeContexts')
      ->willReturn(['group' => $context]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    $storage
      ->expects($this->once())
      ->method('load')
      ->with($groupId)
      ->willReturn($group);

    $result = $this->currentGroupService->fromRunTimeContexts();

    $this->assertSame($group, $result, 'Group should be loaded by ID and returned.');
  }

  /**
   * Test that fromRunTimeContexts() returns NULL when no valid group found.
   */
  public function testFromRunTimeContextsInvalidGroup(): void {
    $invalidValue = 'invalid';
    $context = $this->createContextWithValue($invalidValue);

    $this->groupRouteContext
      ->expects($this->once())
      ->method('getRuntimeContexts')
      ->willReturn(['group' => $context]);

    $result = $this->currentGroupService->fromRunTimeContexts();

    $this->assertNull($result, 'Invalid group value should return NULL.');
  }

}
