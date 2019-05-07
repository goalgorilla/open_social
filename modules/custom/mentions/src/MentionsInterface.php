<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Mention entity.
 */
interface MentionsInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the Mentions creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Mentions.
   */
  public function getCreatedTime();

}
