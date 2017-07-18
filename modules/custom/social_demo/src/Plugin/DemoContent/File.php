<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoFile;

/**
 * File Plugin for demo content.
 *
 * @DemoContent(
 *   id = "file",
 *   label = @Translation("File"),
 *   source = "content/entity/file.yml",
 *   entity_type = "file"
 * )
 */
class File extends DemoFile {

}
