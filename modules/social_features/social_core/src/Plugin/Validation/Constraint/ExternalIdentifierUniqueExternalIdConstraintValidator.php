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
 *  3. External ID should be unique per external owner (External owner entity is
 *     defined by "external_owner_target_type" and external_owner_id". External
 *     ID can be used as many times per entity type, until each external ID is
 *     connected to different external owner).
 *
 * Note:
 * The purpose of the external identifier field is to point to single entity
 * with a unique External ID per entity type. If the same External ID is used
 * multiple times in the same field or across different fields for the same
 * entity, it is not an issue from a relationship perspective. We will always
 * find the correct (and only one) entity associated with the given external ID,
 * even if multiple values point to it. Currently, there is no requirement to
 * limit identical External IDs on the same entity. If such a requirement arises
 * in the future, we will need to update the external ID field constraint and
 * the related kernel test scenarios accordingly. The only known downside of the
 * current development approach is that it allows redundant data in the system.
 * However, this "issue" is not unique to this particular field, as there is no
 * existing system to prevent such redundancy across other fields either.
 *
 * Roadmap Note:
 * In the future, we plan to limit unique External IDs to one per platform
 * (currently, it is limited per entity type). However, this requires
 * development considerations to ensure performance efficiency, as searching
 * for duplicated values across all entity types and fields of type
 * "social_external_identifier" can be resource-intensive. Ideally, we aim
 * to restrict External IDs to be unique per entity, so each ID is stored
 * only once in the database. Currently, it is possible to have redundant
 * unique External IDs for the same entity.
 *
 * Examples:
 *
 * Example 1: This situation is allowed as External ID is unique per entity type
 *            (User 1).
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
 *   | User:2 | field_external_id_2 | 123         | consumer:1     |
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
 * Example 5: This situation is allowed as External ID is unique per entity
 *            type.
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
    $entity_type = $entity->getEntityType();

    // Check per each field of type "social_external_identifier" in given
    // entity type, if external_id is unique per external owner.
    foreach ($this->getFieldNamesByFieldType($entity_type->id()) as $field) {
      $query = $this->entityTypeManager->getStorage($entity_type->id())->getQuery();
      $query->condition($field . '.external_id', $item->external_id);
      $query->condition($field . '.external_owner_target_type', $item->external_owner_target_type);
      $query->condition($field . '.external_owner_id', $item->external_owner_id);
      // Exclude existing current entity.
      if (!$entity->isNew()) {
        $query->condition($entity_type->getKey('id'), $entity->id(), '<>');
      }
      $query->accessCheck(FALSE);
      $result = $query->execute();

      if (!empty($result)) {
        $this->context->addViolation($constraint->externalIdNotUniqueMessage, [
          '%external_id' => $item->external_id,
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
