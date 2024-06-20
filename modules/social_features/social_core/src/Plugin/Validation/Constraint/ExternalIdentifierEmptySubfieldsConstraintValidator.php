<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ExternalIdentifierEmptySubfieldsConstraint constraint.
 */
class ExternalIdentifierEmptySubfieldsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new ExternalIdentifierEmptySubfieldsConstraintValidator.
   *
   * @param \Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager $externalIdentifierManager
   *   The external identifier manager service.
   */
  public function __construct(
    protected ExternalIdentifierManager $externalIdentifierManager
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_core.external_identifier_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $item, Constraint $constraint) {
    if (!$constraint instanceof ExternalIdentifierEmptySubfieldsConstraint) {
      return;
    }

    $values = $item->getValue();
    $empty_subfields = [];

    foreach ($values as $key => $value) {
      if ($value === '' or $value === NULL) {
        $empty_subfields[] = $key;
      }
    }

    // It is allowed that all fields are empty.
    if (count($empty_subfields) === count($values)) {
      return;
    }

    if (count($empty_subfields) > 0) {
      // Add label beside subfield machine name.
      $nice_subfield_labels = [];
      $field_labels = $this->externalIdentifierManager->getSubfieldLabels();
      foreach ($empty_subfields as $empty_subfield) {
        $nice_subfield_labels[] = $field_labels[$empty_subfield] . ' (' . $empty_subfield . ')';
      }
      $this->context->addViolation($constraint->requredSubfieldsAreNotSet, [
        '%empty_required_subfield_list' => implode(', ', $nice_subfield_labels),
      ]);
    }

  }

}
