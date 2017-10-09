<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoSystem;

/**
 * Comment Plugin for demo content.
 *
 * @DemoContent(
 *   id = "system",
 *   label = @Translation("System"),
 *   source = "content/entity/system.yml",
 *   entity_type = "block"
 * )
 */
class System extends DemoSystem {

}
