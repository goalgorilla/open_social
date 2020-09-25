<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field
   *
   * @return \GraphQL\Deferred|null
   */
  public function resolve(EntityInterface $entity, $field) {
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($field)) {
      return NULL;
    }

    return $entity->get($field);
  }

}
