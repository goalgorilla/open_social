<?php

namespace Drupal\Tests\address\Unit\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraint;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
 * @group address
 */
class CountryConstraintValidatorTest extends UnitTestCase {

  /**
   * The constraint.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraint
   */
  protected $constraint;

  /**
   * The validator.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $country_repository = $this->prophesize(CountryRepositoryInterface::class);
    $country_repository->getList()->willReturn(['RS' => 'Serbia', 'FR' => 'France']);

    $this->constraint = new CountryConstraint(['availableCountries' => ['FR']]);
    $this->validator = new CountryConstraintValidator($country_repository->reveal());
  }

  /**
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($address, $expected_violation) {
    // If a violation is expected, then the context's buildViolation method
    // will be called, otherwise it should not be called.
    $context = $this->prophesize(ExecutionContextInterface::class);
    if ($expected_violation) {
      $violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
      $violation_builder->atPath('country_code')->willReturn($violation_builder);
      $violation_builder->setParameter('%value', Argument::any())->willReturn($violation_builder);
      $violation_builder->addViolation()->willReturn($violation_builder);
      $context->buildViolation($expected_violation)->willReturn($violation_builder->reveal())->shouldBeCalled();
    }
    else {
      $context->buildViolation(Argument::any())->shouldNotBeCalled();
    }

    $this->validator->initialize($context->reveal());
    $this->validator->validate($address, $this->constraint);
  }

  /**
   * Data provider for ::testValidate().
   */
  public function providerTestValidate() {
    // Data provides run before setUp, so $this->constraint is not available.
    $constraint = new CountryConstraint();

    $cases = [];
    // Case 1: Empty values.
    $cases[] = [$this->getMockAddress(NULL), FALSE];
    $cases[] = [$this->getMockAddress(''), FALSE];
    // Case 2: Valid country.
    $cases[] = [$this->getMockAddress('FR'), FALSE];
    // Case 3: Invalid country.
    $cases[] = [$this->getMockAddress('InvalidValue'), $constraint->invalidMessage];
    // Case 4: Valid, but unavailable country.
    $cases[] = [$this->getMockAddress('RS'), $constraint->notAvailableMessage];

    return $cases;
  }

  /**
   * @covers ::validate
   *
   * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
   */
  public function testInvalidValueType() {
    $context = $this->prophesize(ExecutionContextInterface::class);
    $this->validator->initialize($context->reveal());
    $this->validator->validate(new \stdClass(), $this->constraint);
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
