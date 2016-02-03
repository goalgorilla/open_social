<?php
/**
 * @file
 * Contains \Drupal\social_user\test\src\Tests\Validation\Constraint\SocialUserNameConstraintTest.
 */
namespace Drupal\social_user\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\social_user\Plugin\Validation\Constraint\SocialUserNameConstraint;
use Drupal\social_user\Plugin\Validation\Constraint\SocialUserNameConstraintValidator;

/**
 * @coversDefaultClass \Drupal\social_user\Plugin\Validation\Constraint\SocialUserNameConstraintValidator
 *
 * @group user
 */
class SocialUserNameConstraintTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function createValidator() {
    return new SocialUserNameConstraintValidator();
  }

  /**
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($items, $expected_violation, $name = FALSE) {
    $constraint = new SocialUserNameConstraint();

    // If a violation is expected, then the context's addViolation method will
    // be called, otherwise it should not be called.
    $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

    if ($expected_violation) {
      $context->expects($this->once())
        ->method('addViolation')
        ->with($constraint->usernameIsEmailMessage, array('%name' => $name));
    }
    else {
      $context->expects($this->never())
        ->method('addViolation');
    }

    $validator = $this->createValidator();
    $validator->initialize($context);
    $validator->validate($items, $constraint);
  }

  /**
   * Data provider for ::testValidate().
   */
  public function providerTestValidate() {
    $cases = [];

    // Case 1: Validation context should not be touched if no items are passed.
    $cases[] = [NULL, FALSE];

    // Case 2: Empty user should be ignored.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('getEntity')
      ->willReturn(NULL);
    $cases[] = [$items, FALSE];

    return $cases;
  }
}