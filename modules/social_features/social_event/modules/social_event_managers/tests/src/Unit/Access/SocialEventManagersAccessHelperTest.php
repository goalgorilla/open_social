<?php

namespace Drupal\social_event_manager\Tests;

use Drupal\Core\Access\AccessResult;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SocialEventManagersAccessHelper class.
 *
 * @group social_event_manager
 */
class SocialEventManagersAccessHelperTest extends UnitTestCase {

  /**
   * Test eventEnrollmentAccessCheck.
   *
   * Test the access check to event enrollment if the recipient is the actual
   * user that got the enrollment.
   */
  public function testAllowedEventEnrollmentAccessForRecipients(): void {
    $event_enrollment = $this->createMock('\Drupal\social_event\Entity\EventEnrollment');

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->any())
      ->method('id')
      ->willReturn("2");

    $field_item = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disallowMockingUnknownTypes()
      ->getMock();

    $field_item->expects($this->any())
      ->method('getString')
      ->willReturn("1");

    $field_account_item = $this->createMock('\Drupal\Core\Field\EntityReferenceFieldItemList');
    $field_account_item->expects($this->any())
      ->method('getString')
      ->willReturn("2");

    $event_enrollment->expects($this->any())
      ->method('get')
      ->willReturnMap([
        [
          'field_enrollment_status',
          $field_item,
        ],
        [
          'field_account',
          $field_account_item,
        ],
      ]);

    $owner = $this->createMock('\Drupal\user\UserInterface');
    $owner->expects($this->any())
      ->method('hasPermission')
      ->willReturn(TRUE);

    $event_enrollment->expects($this->any())
      ->method('getOwner')
      ->willReturn($owner);

    $accessHelper = new SocialEventManagersAccessHelper();
    $returned_access = $accessHelper->eventEnrollmentAccessCheck(
      $event_enrollment,
      'view',
      $account
    );

    $this->assertEquals(AccessResult::allowed(), $returned_access);
  }

  /**
   * Test eventEnrollmentAccessCheck.
   *
   * Test the access check to event enrollment if the recipient is not the
   * user that got the enrollment.
   */
  public function testNeutralEventEnrollmentAccessForRecipients(): void {
    $event_enrollment = $this->createMock('\Drupal\social_event\Entity\EventEnrollment');

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->any())
      ->method('id')
      ->willReturn("2");

    $field_item = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disallowMockingUnknownTypes()
      ->getMock();

    $field_item->expects($this->any())
      ->method('getString')
      ->willReturn("1");

    $field_account_item = $this->createMock('\Drupal\Core\Field\EntityReferenceFieldItemList');
    $field_account_item->expects($this->any())
      ->method('getString')
      ->willReturn("100");

    $event_enrollment->expects($this->any())
      ->method('get')
      ->willReturnMap([
        [
          'field_enrollment_status',
          $field_item,
        ],
        [
          'field_account',
          $field_account_item,
        ],
      ]);

    $owner = $this->createMock('\Drupal\user\UserInterface');
    $owner->expects($this->any())
      ->method('hasPermission')
      ->willReturn(TRUE);

    $event_enrollment->expects($this->any())
      ->method('getOwner')
      ->willReturn($owner);

    $accessHelper = new SocialEventManagersAccessHelper();
    $returned_access = $accessHelper->eventEnrollmentAccessCheck(
      $event_enrollment,
      'view',
      $account
    );

    $this->assertEquals(AccessResult::neutral(), $returned_access);
  }

}
