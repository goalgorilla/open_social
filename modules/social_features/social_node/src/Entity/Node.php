<?php

namespace Drupal\social_node\Entity;

use Drupal\social_core\EntityUrlLanguageTrait;
use Drupal\node\Entity\Node as NodeBase;

/**
 * Provides a Node entity that has links that work with different languages.
 */
class Node extends NodeBase {
  use EntityUrlLanguageTrait;

}
