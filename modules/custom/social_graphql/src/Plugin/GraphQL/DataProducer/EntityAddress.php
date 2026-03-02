<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Loads address data from an entity's address field.
 *
 * Generic producer: pass entity and field name. Returns a GraphQL-shaped array
 * for the Address output type, or NULL when the field is empty/inaccessible.
 *
 * @DataProducer(
 *   id = "entity_address",
 *   name = @Translation("Entity address"),
 *   description = @Translation("Returns the first address from an entity field as a GraphQL Address shape."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Address"),
 *     required = FALSE
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *     "field" = @ContextDefinition("string",
 *       label = @Translation("Field name")
 *     ),
 *   }
 * )
 */
class EntityAddress extends DataProducerPluginBase {

  /**
   * Resolves the address from the entity field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field
   *   The address field name (e.g. field_event_address).
   *
   * @return array|null
   *   Address with GraphQL/camelCase keys, or NULL when empty/inaccessible.
   */
  public function resolve(EntityInterface $entity, string $field): ?array {
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($field)) {
      return NULL;
    }

    $fieldItemList = $entity->get($field);
    if (!$fieldItemList->access('view') || $fieldItemList->isEmpty()) {
      return NULL;
    }

    $item = $fieldItemList->first();
    if ($item === NULL) {
      return NULL;
    }

    $value = $item->getValue();
    if (!is_array($value)) {
      return NULL;
    }

    return [
      'countryCode' => $value['country_code'] ?? NULL,
      'administrativeArea' => $value['administrative_area'] ?? NULL,
      'locality' => $value['locality'] ?? NULL,
      'dependentLocality' => $value['dependent_locality'] ?? NULL,
      'postalCode' => $value['postal_code'] ?? NULL,
      'addressLine1' => $value['address_line1'] ?? NULL,
      'addressLine2' => $value['address_line2'] ?? NULL,
    ];
  }

}
