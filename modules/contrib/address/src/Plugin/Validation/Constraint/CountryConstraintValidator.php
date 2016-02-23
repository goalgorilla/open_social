<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the country constraint.
 */
class CountryConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new CountryConstraintValidator object.
   *
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(CountryRepositoryInterface $country_repository) {
    $this->countryRepository = $country_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('address.country_repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!($value instanceof AddressInterface)) {
      throw new UnexpectedTypeException($value, 'AddressInterface');
    }

    $address = $value;
    $country_code = $address->getCountryCode();
    if ($country_code === NULL || $country_code === '') {
      return;
    }

    $countries = $this->countryRepository->getList();
    if (!isset($countries[$country_code])) {
      $this->context->buildViolation($constraint->invalidMessage)
        ->atPath('country_code')
        ->setParameter('%value', $this->formatValue($country_code))
        ->addViolation();
      return;
    }

    $available_countries = $constraint->availableCountries;
     if (!empty($available_countries) && !in_array($country_code, $available_countries)) {
       $this->context->buildViolation($constraint->notAvailableMessage)
          ->atPath('country_code')
          ->setParameter('%value', $this->formatValue($country_code))
          ->addViolation();
    }
  }

}
