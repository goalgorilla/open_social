<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the label of an entity without access check.
 *
 * This resolver always returns the entity label without checking
 * access permissions. This is useful when the API needs to returns.
 *
 * @DataProducer(
 *   id = "entity_label",
 *   name = @Translation("Entity label"),
 *   description = @Translation("Returns the entity label without access check."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Label")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *   }
 * )
 */
class EntityLabel extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolver.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Returns the entity label, always non-null.
   */
  public function resolve(EntityInterface $entity): string {
    // Always return the label without checking access.
    $label = $entity->label();
    return $label !== NULL ? (string) $label : '';
  }

}
