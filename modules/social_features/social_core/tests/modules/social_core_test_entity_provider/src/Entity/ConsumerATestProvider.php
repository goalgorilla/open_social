<?php

namespace Drupal\social_core_test_entity_provider\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the Consumer A Test entity.
 *
 * @ContentEntityType(
 *   id = "test_consumer_a",
 *   label = @Translation("Test consumer A"),
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "id" = "id",
 *     "label" = "name",
 *   }
 * )
 */
class ConsumerATestProvider extends EntityTest {

}
