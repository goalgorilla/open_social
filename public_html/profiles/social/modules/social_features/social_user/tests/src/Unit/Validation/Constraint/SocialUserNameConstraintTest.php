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
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($items, $expected_violation, $expected_definition_result = NULL) {
    // Mock our typed data interface.
    $manager = $this->getMock('Drupal\Core\TypedData\TypedDataManagerInterface');
    $definition = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');

    if ($expected_definition_result !== NULL) {
      $manager->expects($this->once())
        ->method('create')
        ->willReturn($definition);

      $definition->expects($this->once())
        ->method('validate')
        ->willReturn($expected_definition_result);
    }
    else {
      $manager->expects($this->never())
        ->method('create');

      $definition->expects($this->never())
        ->method('validate');
    }

    $constraint = new SocialUserNameConstraint();
    $constraintValidator = new SocialUserNameConstraintValidator($manager);

    // If a violation is expected, then the context's addViolation method will
    // be called, otherwise it should not be called.
    $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

    if ($expected_violation) {
      $context->expects($this->once())
        ->method('addViolation')
        ->with($constraint->usernameIsEmailMessage);
    }
    else {
      $context->expects($this->never())
        ->method('addViolation');
    }

    $constraintValidator->initialize($context);
    $constraintValidator->validate($items, $constraint);
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
      ->method('first')
      ->willReturn(NULL);
    $cases[] = [$items, FALSE];

    // Case 3: E-mail
    $name_field = $this->getMock('Drupal\Core\Field\FieldItemInterface');
    $name_field->expects($this->once())
      ->method('__get')
      ->willReturn('email@example.com');

    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $items = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $items->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    $items->expects($this->once())
      ->method('first')
      ->willReturn($name_field);
    $cases[] = [$items, FALSE, 0];

    return $cases;
  }
}