<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ExternalIdentifierUniqueExternalIdConstraint constraint.
 *
 * Uniqueness rule:
 *   External ID should be unique per entity and external owner, despite how
 *   many fields of type "social_external_identifier" entity have.
 *
 * Uniqueness rules broken down in details:
 *  1. External ID should be unique per entity type (for example: external ID
 *     can be used only once per user entity).
 *  2. External ID should be unique per all “social_external_identifier” fields
 *     (Unlimited amount of fields of type social_external_identifier can be
 *     added per one entity and even among all those fields External ID should
 *     be unique).
 *  3. external ID should be unique per external owner (External owner entity is
 *     defined by "external_owner_target_type" and external_owner_id". External
 *     ID can be used as many times per entity type, until each external ID is
 *     connected to different external owner).
 *
 * Examples:
 *
 * Example 1: This situation is forbidden as External ID is not unique.
 *
 *   | Entity | Field                       | External ID | External owner |
 *   | ------ | --------------------------- | ----------- | -------------- |
 *   | User:1 | field_external_identifier_1 | 123         | consumer:1     |
 *   | User:1 | field_external_identifier_1 | 123         | consumer:1     |
 *
 *  Example 2: This situation is forbidden as two users can not have same
 *             External ID.
 *
 *    | Entity | Field               | External ID | External owner |
 *    | ------ | ------------------- | ----------- | -------------- |
 *    | User:1 | field_external_id_1 | 123         | consumer:1     |
 *    | User:2 | field_external_id_1 | 123         | consumer:1     |
 *
 * Example 3: This situation is forbidden as same External ID is not allowed
 *            even within two different fields within same entity type (User).
 *
 *   | Entity | Field               | External ID | External owner |
 *   | ------ | ------------------- | ----------- | -------------- |
 *   | User:1 | field_external_id_1 | 123         | consumer:1     |
 *   | User:1 | field_external_id_2 | 123         | consumer:1     |
 *
 * Example 4: This situation is forbidden as same External ID is not allowed
 *            even within two different entity bundles within same entity
 *            type (Group).
 *
 *   | Entity  | Bundle   | Field               | External ID | External owner |
 *   | ------- | -------- | ------------------- | ----------- | -------------- |
 *   | Group:1 | course   | field_external_id_1 | 123         | consumer:1     |
 *   | Group:2 | flexible | field_external_id_2 | 123         | consumer:1     |
 *
 * Example 5: This situation is allowed as External ID is unique per entity.
 *
 *   | Entity  | Field               | External ID | External owner |
 *   | ------- | ------------------- | ----------- | -------------- |
 *   | User:1  | field_external_id_1 | 123         | consumer:1     |
 *   | Group:1 | field_external_id_1 | 123         | consumer:1     |
 *
 * Example 6: This situation is allowed as External ID is unique per external
 *            owner.
 *
 *   | Entity | Field               | External ID | External owner |
 *   | ------ | ------------------- | ----------- | -------------- |
 *   | User:1 | field_external_id_1 | 123         | consumer:1     |
 *   | User:1 | field_external_id_1 | 123         | consumer:2     |
 *   | User:1 | field_external_id_1 | 123         | owner:1        |
 */
class ExternalIdentifierUniqueExternalIdConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  const EXTERNAL_IDENTIFIER_FIELD_TYPE = 'social_external_identifier';

  /**
   * Constructs a new constraint validator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $item, Constraint $constraint) {
    if (!$constraint instanceof ExternalIdentifierUniqueExternalIdConstraint) {
      return;
    }

    // Empty constraint is handled by
    // ExternalIdentifierEmptySubfieldsConstraint.
    if ($item->isEmpty()) {
      return;
    }

    $entity = $item->getEntity();
    $entity_type = $entity->getEntityTypeId();

    // Check per each field of type "social_external_identifier" in given
    // entity type, if external_id is unique per external owner.
    foreach ($this->getFieldNamesByFieldType($entity_type) as $field) {
      $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
      $query->condition($field . '.external_id', $item->external_id);
      $query->condition($field . '.external_owner_target_type', $item->external_owner_target_type);
      $query->condition($field . '.external_owner_id', $item->external_owner_id);
      // Exclude existing current entity.
      if (!$entity->isNew()) {
        $query->condition('id', $entity->id(), '<>');
      }
      $query->accessCheck(FALSE);
      $result = $query->execute();

      if (!empty($result)) {
        $this->context->addViolation($constraint->externalIdNotUniqueMessage, [
          '%external_id' => $item->external_id,
          // @todo Do we even want to expose internal owner target type and id?
          '%external_owner_target_type' => $item->external_owner_target_type,
          '%external_owner_id' => $item->external_owner_id,
        ]);
      }
    }

  }

  /**
   * Get all field names of type 'social_external_identifier'.
   *
   * Get all field names of type 'social_external_identifier'
   * for a given entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   *
   * @return array
   *   An array of field names.
   */
  private function getFieldNamesByFieldType(string $entity_type): array {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    $social_external_identifier_fields = [];

    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->getType() === self::EXTERNAL_IDENTIFIER_FIELD_TYPE) {
        $social_external_identifier_fields[] = $field_name;
      }
    }

    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type);

    foreach ($base_fields as $base_field_name => $field_definition) {
      if ($field_definition->getType() === self::EXTERNAL_IDENTIFIER_FIELD_TYPE) {
        $social_external_identifier_fields[] = $base_field_name;
      }
    }

    return $social_external_identifier_fields;
  }

}
