<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the URL of an entity.
 *
 * @DataProducer(
 *   id = "social_entity_url",
 *   name = @Translation("Entity url"),
 *   description = @Translation("Returns the entity's url."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Url")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *     "options" = @ContextDefinition("any",
 *       label = @Translation("URL Options"),
 *       description = @Translation("Options to pass to the toUrl call"),
 *       required = FALSE
 *     )
 *   }
 * )
 *
 * @internal
 * @deprecated remove when upgrading graphql module to a version that
 * includes the options dataproducer setting.
 * @see https://github.com/drupal-graphql/graphql/pull/1220
 */
class EntityUrl extends DataProducerPluginBase {

  /**
   * Resolver.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create a canonical URL for.
   * @param null|array $options
   *   The options to provide to the URL generator.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function resolve(EntityInterface $entity, ?array $options) {
    return $entity->toUrl('canonical', $options ?? []);
  }

}
