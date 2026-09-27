<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Action;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_core\TestControlInterface\Error\EntityAmbiguous;
use Drupal\social_core\TestControlInterface\Error\EntityNotFound;
use Drupal\social_core\TestControlInterface\Error\UnknownBundle;
use Drupal\social_core\TestControlInterface\Error\UnknownEntityType;
use Drupal\social_core\TestControlInterface\Success\EntityByLabelSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;

/**
 * TCI endpoint for looking up any content entity by its label.
 *
 * Lets a test create data by name and find its ID or UUID when it runs,
 * instead of depending on a specific ID or UUID.
 */
final class GetEntityByLabel {

  /**
   * Short entity type names accepted in place of the entity type ID.
   */
  private const ENTITY_TYPE_ALIASES = [
    'term' => 'taxonomy_term',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    private readonly EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Returns the ID, UUID, and label of the entity with the given label.
   *
   * Matches the entity type's label field (a node's title, a term's name)
   * within one bundle, without an access check, the way the database
   * compares it. Exactly one entity must match: a missing or duplicated
   * label fails, so the step doubles as an existence check.
   *
   * @param string $bundle
   *   The bundle, such as a node type or a vocabulary ID.
   * @param string $entity_type
   *   The entity type ID, or 'term' for taxonomy_term.
   * @param string $label
   *   The label to look up.
   *
   * @return \Drupal\social_core\TestControlInterface\Success\EntityByLabelSuccess|\Drupal\social_core\TestControlInterface\Error\EntityNotFound|\Drupal\social_core\TestControlInterface\Error\EntityAmbiguous|\Drupal\social_core\TestControlInterface\Error\UnknownEntityType|\Drupal\social_core\TestControlInterface\Error\UnknownBundle
   *   The operation result.
   */
  #[Operation(
    id: 'entity_by_label',
    patterns: [
      'the :bundle :entity_type with title :label',
      'the :bundle :entity_type with name :label',
    ],
    type: OperationType::Read,
  )]
  public function getEntityByLabel(
    #[Example('topic_types')]
    string $bundle,
    #[Example('term')]
    string $entity_type,
    #[Example('News')]
    string $label,
  ): EntityByLabelSuccess|EntityNotFound|EntityAmbiguous|UnknownEntityType|UnknownBundle {
    $entityTypeId = self::ENTITY_TYPE_ALIASES[$entity_type] ?? $entity_type;
    $entityType = $this->entityTypeManager->getDefinition($entityTypeId, FALSE);
    if (!$entityType instanceof ContentEntityTypeInterface) {
      return new UnknownEntityType($entity_type, 'is not a content entity type');
    }

    $labelKey = $entityType->getKey('label');
    if (!is_string($labelKey) || $labelKey === '') {
      return new UnknownEntityType($entity_type, 'has no label field');
    }

    $idKey = $entityType->getKey('id');
    $idField = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId)[$idKey] ?? NULL;
    if ($idField?->getType() !== 'integer') {
      return new UnknownEntityType($entity_type, 'has no integer IDs');
    }

    if (!array_key_exists($bundle, $this->entityTypeBundleInfo->getBundleInfo($entityTypeId))) {
      return new UnknownBundle($entityTypeId, $bundle);
    }

    $query = $this->entityTypeManager->getStorage($entityTypeId)->getQuery()
      ->accessCheck(FALSE)
      ->condition($labelKey, $label);
    $bundleKey = $entityType->getKey('bundle');
    if (is_string($bundleKey) && $bundleKey !== '') {
      $query->condition($bundleKey, $bundle);
    }
    $ids = array_values($query->execute());

    if ($ids === []) {
      return new EntityNotFound($entityTypeId, $bundle, $label);
    }
    if (count($ids) > 1) {
      return new EntityAmbiguous($entityTypeId, $bundle, $label, array_map('intval', $ids));
    }

    $entity = $this->entityTypeManager->getStorage($entityTypeId)->load($ids[0]);
    assert($entity !== NULL);

    return new EntityByLabelSuccess(id: (int) $entity->id(), uuid: (string) $entity->uuid(), label: (string) $entity->label());
  }

}
