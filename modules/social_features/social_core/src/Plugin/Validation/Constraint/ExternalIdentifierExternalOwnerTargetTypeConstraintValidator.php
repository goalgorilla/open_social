<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ExternalIdentifierExternalOwnerTargetTypeConstraint constraint.
 */
class ExternalIdentifierExternalOwnerTargetTypeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new constraint validator.
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
    if (!$constraint instanceof ExternalIdentifierExternalOwnerTargetTypeConstraint) {
      return;
    }
    $external_owner_target_type = $item->external_owner_target_type;

    // Empty constraint is handled by
    // ExternalIdentifierEmptySubfieldsConstraint.
    if (empty($external_owner_target_type)) {
      return;
    }
    // Check if entity type is on list of allowed external owner target types.
    if (!in_array($external_owner_target_type, array_keys($this->externalIdentifierManager->getAllowedExternalOwnerTargetTypes()))) {
      $this->context->addViolation($constraint->invalidTargetTypeMessage, [
        '%invalid_target_type' => $external_owner_target_type,
        '%allowed_target_types' => $this->externalIdentifierManager->getValidTargetTypesAsString(),
      ]);
    }

    // Check if the entity type exists (this is triggered only if entity type is
    // listed on allowed external owner target types list, while entity type
    // does not exist as such.
    if (!$this->entityTypeManager->hasDefinition($external_owner_target_type)) {
      $this->context->addViolation($constraint->nonexistentTargetTypeMessage, ['%entity_type' => $external_owner_target_type]);
    }

  }

}
