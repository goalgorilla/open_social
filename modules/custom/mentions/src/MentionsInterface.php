<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
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
  public function getCreatedTime(): int;

  /**
   * Get the mentioned entity.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   Return the entity.
   */
  public function getMentionedEntity(): ?EntityInterface;

  /**
   * Get the mentioned entity id.
   *
   * @return int
   *   Returns the entity id.
   */
  public function getMentionedEntityId(): int;

  /**
   * Get the mentioned entity type id.
   *
   * @return string
   *   Returns the entity type id.
   */
  public function getMentionedEntityTypeId(): string;

  /**
   * Get mentioned user id.
   *
   * @return int
   *   Returns the used id.
   */
  public function getMentionedUserId(): int;

}
