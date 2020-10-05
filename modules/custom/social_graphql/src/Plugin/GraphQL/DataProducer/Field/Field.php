<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Produces a field instance from an entity.
 *
 * Can be used instead of the property path when information about the field
 * item must be queryable. The property_path resolver always returns an array
 * which sometimes causes information loss.
 *
 * @DataProducer(
 *   id = "field",
 *   name = @Translation("Field"),
 *   description = @Translation("Selects a field from an entity."),
 *   produces = @ContextDefinition("mixed",
 *     label = @Translation("Field")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Parent entity")
 *     ),
 *     "field" = @ContextDefinition("string",
 *       label = @Translation("Field name")
 *     )
 *   }
 * )
 */
class Field extends DataProducerPluginBase {

  /**
   * Finds the requested field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that contains the field.
   * @param string $field
   *   The name of the field to return.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   A field item list if the field exists or null if the entity is not
   *   fieldable or doesn't have the requested field.
   */
  public function resolve(EntityInterface $entity, string $field): ?FieldItemListInterface {
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($field)) {
      return NULL;
    }

    $value = $entity->get($field);
    // A FieldableEntityInterface::get will always return a
    // FieldItemListInterface which implements AccessibleInterface. Thus no
    // further typechecking is needed.
    return $value->access('view') ? $value : NULL;
  }

}
