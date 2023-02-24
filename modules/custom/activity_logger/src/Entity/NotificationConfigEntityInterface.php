<?php

namespace Drupal\activity_logger\Entity;

/**
 * Provides an interface for a config entity that can use notification.
 */
interface NotificationConfigEntityInterface {

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   Creation timestamp of entity.
   */
  public function getCreatedTime(): int;

  /**
   * Gets the unique id.
   *
   * @return int
   *   Unique id to reference the entity.
   */
  public function getUniqueId(): int;

}
