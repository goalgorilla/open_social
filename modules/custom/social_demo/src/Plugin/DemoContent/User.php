<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoUser;

/**
 * User Plugin for demo content.
 *
 * @DemoContent(
 *   id = "user",
 *   label = @Translation("User"),
 *   source = "content/entity/user.yml",
 *   entity_type = "user"
 * )
 */
class User extends DemoUser {

}
