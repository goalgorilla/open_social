<?php

namespace Drupal\social_core_test_entity_provider\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the Consumer B Test entity.
 *
 * @ContentEntityType(
 *   id = "test_consumer_b",
 *   label = @Translation("Test consumer B"),
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "id" = "id",
 *     "label" = "name",
 *   }
 * )
 */
class ConsumerBTestProvider extends EntityTest {

}
