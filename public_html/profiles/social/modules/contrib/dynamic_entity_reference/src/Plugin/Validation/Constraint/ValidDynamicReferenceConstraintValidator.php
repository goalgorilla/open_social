<?php

namespace Drupal\dynamic_entity_reference\Plugin\Validation\Constraint;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidDynamicReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }
    // We don't use a regular NotNull constraint for the target_id property as
    // a NULL value is valid if the entity property contains an unsaved entity.
    // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints
    if (!$value->isEmpty() && $value->target_id === NULL && !$value->entity->isNew()) {
      $this->context->addViolation($constraint->nullMessage, ['%property' => 'target_id']);
      return;
    }
    // We don't use a regular NotNull constraint for the target_id property as
    // a NULL value is valid if the entity property contains an unsaved entity.
    // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints
    if (!$value->isEmpty() && $value->target_type === NULL && !$value->entity->isNew()) {
      $this->context->addViolation($constraint->nullMessage, ['%property' => 'target_type']);
      return;
    }
    $id = $value->get('target_id')->getValue();
    $type = $value->get('target_type')->getValue();
    $types = DynamicEntityReferenceItem::getTargetTypes($value->getFieldDefinition()->getSettings());
    $valid_type = empty($type) || (!empty($type) && in_array($type, $types));
    // '0' or NULL are considered valid empty references.
    if (empty($id) && $valid_type) {
      return;
    }
    $referenced_entity = $value->get('entity')->getValue();
    /** @var \Drupal\dynamic_entity_reference\Plugin\Validation\Constraint\ValidDynamicReferenceConstraint $constraint */
    if (!$valid_type || !$referenced_entity) {
      $this->context->addViolation($constraint->message, array('%type' => $type, '%id' => $id));
    }
  }

}
