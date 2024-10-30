<?php

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
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
   * The mocked context repository interface.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ContextRepositoryInterface|MockObject $contextRepository;

  /**
   * The service under test.
   *
   * @var \Drupal\social_group\CurrentGroupService
   */
  protected CurrentGroupService $currentGroupService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->contextRepository = $this->createMock(ContextRepositoryInterface::class);

    $this->currentGroupService = new CurrentGroupService($this->contextRepository);
  }

  /**
   * Test that fromRunTimeContexts() returns a Group when a group is present.
   */
  public function testFromRunTimeContextsWithGroup(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->mockContext($group);

    // Call the method and assert the group is returned.
    $result = $this->currentGroupService->fromRunTimeContexts();
    $this->assertSame($group, $result, 'Group context should return a GroupInterface instance.');
  }

  /**
   * Test that fromRunTimeContexts() returns NULL when group is not present.
   */
  public function testFromRunTimeContextsWithoutGroup(): void {
    $this->mockContext(NULL);

    // Call the method and assert NULL is returned.
    $result = $this->currentGroupService->fromRunTimeContexts();
    $this->assertNull($result, 'Group context should return NULL.');
  }

  /**
   * Mock the context.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject|\Drupal\group\Entity\GroupInterface|null $group
   *   The mocked group or NULL.
   */
  private function mockContext(MockObject|GroupInterface|NULL $group): void {
    $typedData = $this->createMock(TypedDataInterface::class);
    $typedData->expects($this->once())
      ->method('getValue')
      ->willReturn($group);

    $context = $this->createMock(ContextInterface::class);
    $context->expects($this->once())
      ->method('getContextData')
      ->willReturn($typedData);

    $this->contextRepository
      ->expects($this->once())
      ->method('getRuntimeContexts')
      ->with(['@group.group_route_context:group'])
      ->willReturn(['@group.group_route_context:group' => $context]);
  }

}
