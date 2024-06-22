<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ExternalIdentifierExternalOwnerIdConstraint constraint.
 */
class ExternalIdentifierExternalOwnerIdConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new ExternalIdentifierExternalOwnerIdConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $item, Constraint $constraint) {
    if (!$constraint instanceof ExternalIdentifierExternalOwnerIdConstraint) {
      return;
    }

    $target_type = $item->target_type;
    $target_id = $item->target_id;

    // Nonexistent entity type validation is handled by
    // ExternalIdentifierExternalOwnerTargetTypeConstraint.
    if (!$this->entityTypeManager->hasDefinition($target_type)) {
      return;
    }

    $entity = $this->entityTypeManager->getStorage($target_type)->load($target_id);
    if (empty($entity)) {
      $this->context->addViolation($constraint->nonexistentIdMessage, [
        '%entity_type' => $target_type,
        '%entity_id' => $target_id,
      ]);

    }

  }

}
