<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Mention entity
 */
interface MentionsInterface extends ContentEntityInterface {

  /**
   * Gets the Mentions creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Mentions.
   */
  public function getCreatedTime();

}
