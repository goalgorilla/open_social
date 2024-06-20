<?php

namespace Drupal\social_core_test_entity_provider\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the External Owner Test entity.
 *
 * @ContentEntityType(
 *   id = "test_external_owner_entity",
 *   label = @Translation("Test External Owner Entity"),
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "id" = "id",
 *     "label" = "name",
 *   }
 * )
 */
class ExternalOwnerEntityTestProvider extends EntityTest {

}
