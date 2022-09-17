<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides helpers around entity management.
 */
trait EntityTrait {

  /**
   * Validate that the specified values are actually fields on the entity.
   *
   * Throws an exception if the user provided fields that aren't part of the
   * entity. This helps prevent tests that make statements of "given" entities
   * while some of the data may not actually have been persisted giving false
   * negatives on later assertions.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $values
   *   The provided values.
   */
  protected function validateEntityFields(string $entity_type, array $values) : void {
    $definition = \Drupal::service("entity_type.manager")->getDefinition($entity_type);
    assert($definition instanceof EntityTypeInterface);
    /** @var ?string $bundle */
    $bundle = $definition->getKey('bundle') ?: NULL;
    if ($bundle !== NULL && !isset($values[$bundle])) {
      throw new \Exception("Must specify '$bundle' for '$entity_type' type entity.");
    }

    $entityClass = $definition->getClass();
    /** @var \Drupal\Core\Entity\EntityInterface $dummy */
    $dummy = $entityClass::create([$bundle => $values[$bundle]]);

    foreach ($values as $field => $_) {
      if ($definition->get($field) === NULL && !($dummy instanceof FieldableEntityInterface && $dummy->hasField($field))) {
        throw new \Exception("Entity type '$entity_type' does not have property or field '$field'.");
      }
    }
  }

}
