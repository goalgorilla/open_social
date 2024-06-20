<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager;
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
   * @param \Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager $externalIdentifierManager
   *   The external identifier manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected ExternalIdentifierManager $externalIdentifierManager,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_core.external_identifier_manager'),
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

    $external_owner_target_type = $item->external_owner_target_type;
    $external_owner_id = $item->external_owner_id;

    // Nonexistent entity type validation is handled by
    // ExternalIdentifierExternalOwnerTargetTypeConstraint.
    if (!$this->entityTypeManager->hasDefinition($external_owner_target_type)) {
      return;
    }

    $entity = $this->entityTypeManager->getStorage($external_owner_target_type)->load($external_owner_id);
    if (empty($entity)) {
      $this->context->addViolation($constraint->nonexistentIdMessage, [
        '%entity_type' => $external_owner_target_type,
        '%entity_id' => $external_owner_id,
      ]);

    }

  }

}
