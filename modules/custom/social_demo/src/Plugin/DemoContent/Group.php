<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoGroup;

/**
 * Group Plugin for demo content.
 *
 * @DemoContent(
 *   id = "group",
 *   label = @Translation("Group"),
 *   source = "content/entity/group.yml",
 *   entity_type = "group"
 * )
 */
class Group extends DemoGroup {

}
