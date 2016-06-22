<?php

/**
 * @file
 * Contains \Drupal\Tests\address\Plugin\Validation\Constraint\CountryConstraintValidatorTest.
 */

namespace Drupal\Tests\address\Unit\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\CountryConstraint;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest;

/**
 * @coversDefaultClass \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
 * @group address
 */
class CountryConstraintValidatorTest extends AbstractConstraintValidatorTest {

  /**
   * The constraint.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraint
   */
  protected $constraint;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->constraint = new CountryConstraint(['availableCountries' => ['FR']]);

    // The following code is copied from the parent setUp(), which isn't
    // called to avoid the call to \Locale, which introduces a dependency
    // on the intl extension (or symfony/intl).
    $this->group = 'MyGroup';
    $this->metadata = NULL;
    $this->object = NULL;
    $this->value = 'InvalidValue';
    $this->root = 'root';
    $this->propertyPath = '';
    $this->context = $this->createContext();
    $this->validator = $this->createValidator();
    $this->validator->initialize($this->context);
  }

  protected function createValidator() {
    $country_repository = $this->getMock('CommerceGuys\Addressing\Repository\CountryRepositoryInterface');
    $country_repository->expects($this->any())
      ->method('getList')
      ->willReturn(['FR' => 'France', 'RS' => 'Serbia']);

    return new CountryConstraintValidator($country_repository);
  }

  /**
   * @covers ::validate
   */
  public function testEmptyIsValid() {
    $this->validator->validate($this->getMockAddress(NULL), $this->constraint);
    $this->assertNoViolation();

    $this->validator->validate($this->getMockAddress(''), $this->constraint);
    $this->assertNoViolation();
  }

  /**
   * @covers ::validate
   *
   * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
   */
  public function testInvalidValueType() {
    $this->validator->validate(new \stdClass(), $this->constraint);
  }

  /**
   * @covers ::validate
   */
  public function testInvalidCountry() {
    $this->validator->validate($this->getMockAddress('InvalidValue'), $this->constraint);
    $this->buildViolation($this->constraint->invalidMessage)
      ->setParameters(['%value' => '"InvalidValue"'])
      ->atPath('country_code')
      ->assertRaised();
  }

  /**
   * @covers ::validate
   */
  public function testNotAvailableCountry() {
    $this->validator->validate($this->getMockAddress('RS'), $this->constraint);
    $this->buildViolation($this->constraint->notAvailableMessage)
      ->setParameters(['%value' => '"RS"'])
      ->atPath('country_code')
      ->assertRaised();
  }

  /**
   * @covers ::validate
   */
  public function testValidCountry() {
    $this->validator->validate($this->getMockAddress('FR'), $this->constraint);
    $this->assertNoViolation();
  }

  /**
   * Gets a mock address.
   *
   * @param string $country_code
   *   The country code to return via $address->getCountryCode().
   *
   * @return \Drupal\address\AddressInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mock address.
   */
  protected function getMockAddress($country_code) {
    $address = $this->getMockBuilder('Drupal\address\AddressInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $address->expects($this->any())
      ->method('getCountryCode')
      ->willReturn($country_code);

    return $address;
  }

}
