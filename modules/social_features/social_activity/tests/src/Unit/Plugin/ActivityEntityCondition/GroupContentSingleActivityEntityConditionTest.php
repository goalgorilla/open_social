<?php

declare(strict_types=1);

namespace Drupal\Tests\social_activity\Unit\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\social_activity\Plugin\ActivityEntityCondition\GroupContentSingleActivityEntityCondition;
use Drupal\social_group\CrossPostingService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for GroupContentSingleActivityEntityCondition.
 *
 * @group social_activity
 *
 * @coversDefaultClass \Drupal\social_activity\Plugin\ActivityEntityCondition\GroupContentSingleActivityEntityCondition
 */
final class GroupContentSingleActivityEntityConditionTest extends UnitTestCase {

  /**
   * The cross-posting service mock.
   *
   * @var \Drupal\social_group\CrossPostingService&\PHPUnit\Framework\MockObject\MockObject
   */
  private CrossPostingService&MockObject $crossPostingService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->crossPostingService = $this->createMock(CrossPostingService::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // The marker is a request scoped static: a value leaking from one test
    // method into the next would make these assertions lie.
    FirstPublicationMarker::setRaised(FALSE);

    parent::tearDown();
  }

  /**
   * An entity type the condition does not handle is never valid.
   *
   * @covers ::isValidEntityCondition
   */
  public function testOtherEntityTypeIsNotValid(): void {
    $this->crossPostingService->expects($this->never())
      ->method('nodeExistsInMultipleGroups');
    $this->crossPostingService->expects($this->never())
      ->method('notifiesAllGroups');

    $this->assertFalse($this->plugin()->isValidEntityCondition($this->entity('node')));
  }

  /**
   * Content posted in a single group is valid without reading the setting.
   *
   * This is the untouched path: it must keep working exactly as before, and in
   * particular it must not depend on the new configuration key.
   *
   * @covers ::isValidEntityCondition
   */
  public function testSingleGroupIsValidWithoutReadingTheSetting(): void {
    $entity = $this->entity('group_content');

    $this->crossPostingService->expects($this->once())
      ->method('nodeExistsInMultipleGroups')
      ->with($entity)
      ->willReturn(FALSE);
    $this->crossPostingService->expects($this->never())
      ->method('notifiesAllGroups');

    $this->assertTrue($this->plugin()->isValidEntityCondition($entity));
  }

  /**
   * A post entity is handled by the condition, not skipped like other types.
   *
   * Posts reach the cross-posting branch rather than the early FALSE return
   * that every other entity type gets: this pins the "post" member of the
   * handled entity type list, which the single-group tests above cannot,
   * because they are satisfied by "group_content" alone.
   *
   * @covers ::isValidEntityCondition
   */
  public function testPostEntityIsHandledLikeGroupContent(): void {
    $entity = $this->entity('post');

    $this->crossPostingService->expects($this->once())
      ->method('nodeExistsInMultipleGroups')
      ->with($entity)
      ->willReturn(TRUE);
    $this->expectSetting(TRUE);
    FirstPublicationMarker::setRaised(TRUE);

    $this->assertTrue($this->plugin()->isValidEntityCondition($entity));
  }

  /**
   * Cross-posted content is not valid while the setting is disabled.
   *
   * @covers ::isValidEntityCondition
   */
  public function testCrossPostedIsNotValidWhenSettingDisabled(): void {
    $this->expectSetting(FALSE);
    FirstPublicationMarker::setRaised(TRUE);

    $this->assertFalse($this->plugin()->isValidEntityCondition($this->crossPostedEntity()));
  }

  /**
   * Cross-posted content is valid while it is being made visible.
   *
   * @covers ::isValidEntityCondition
   */
  public function testCrossPostedIsValidWhenSettingEnabledAndMarkerRaised(): void {
    $this->expectSetting(TRUE);
    FirstPublicationMarker::setRaised(TRUE);

    $this->assertTrue($this->plugin()->isValidEntityCondition($this->crossPostedEntity()));
  }

  /**
   * Adding a group to existing content does not notify, even when enabled.
   *
   * @covers ::isValidEntityCondition
   */
  public function testCrossPostedIsNotValidWhenMarkerLowered(): void {
    $this->expectSetting(TRUE);
    FirstPublicationMarker::setRaised(FALSE);

    $this->assertFalse($this->plugin()->isValidEntityCondition($this->crossPostedEntity()));
  }

  /**
   * Builds the plugin under test.
   *
   * @return \Drupal\social_activity\Plugin\ActivityEntityCondition\GroupContentSingleActivityEntityCondition
   *   The plugin.
   */
  private function plugin(): GroupContentSingleActivityEntityCondition {
    return new GroupContentSingleActivityEntityCondition(
      [],
      'group_content_node_single_group',
      [],
      $this->crossPostingService,
    );
  }

  /**
   * Builds an entity mock of the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type id the mock reports.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The entity mock.
   */
  private function entity(string $entity_type_id): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entity_type_id);

    return $entity;
  }

  /**
   * Builds a group_content entity that is reported as cross-posted.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The entity mock.
   */
  private function crossPostedEntity(): ContentEntityInterface&MockObject {
    $entity = $this->entity('group_content');

    $this->crossPostingService->expects($this->once())
      ->method('nodeExistsInMultipleGroups')
      ->with($entity)
      ->willReturn(TRUE);

    return $entity;
  }

  /**
   * Makes the cross-posting service answer notifiesAllGroups() once.
   *
   * @param bool $value
   *   What the service reports; the status and notify-all-groups combinations
   *   behind it are covered by CrossPostingServiceTest.
   */
  private function expectSetting(bool $value): void {
    $this->crossPostingService->expects($this->once())
      ->method('notifiesAllGroups')
      ->willReturn($value);
  }

}
