<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraintValidator.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\Model\AddressFormatInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Validator\Constraints\AddressFormatValidator as ExternalValidator;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the address format constraint.
 */
class AddressFormatConstraintValidator extends ExternalValidator implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.address_format_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function addViolation($field, $message, $invalid_value, AddressFormatInterface $address_format) {
    $labels = LabelHelper::getFieldLabels($address_format);
    $label = $labels[$field];

    $this->context->buildViolation($message, ['@name' => $label])
      ->atPath(FieldHelper::getPropertyName($field))
      ->setInvalidValue($invalid_value)
      ->addViolation();
  }

}
