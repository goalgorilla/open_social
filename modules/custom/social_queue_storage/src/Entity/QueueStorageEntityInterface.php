<?php

namespace Drupal\social_queue_storage\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Queue storage entity entities.
 *
 * @ingroup social_queue_storage
 */
interface QueueStorageEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Returns the queue storage type.
   *
   * @return string
   *   The queue storage type name.
   */
  public function getType(): string;

  /**
   * Sets the queue storage type.
   *
   * @param string $type
   *   The queue storage type.
   *
   * @return $this
   */
  public function setType($type);

  /**
   * Gets the Queue storage entity name.
   *
   * @return string
   *   Name of the Queue storage entity.
   */
  public function getName(): string;

  /**
   * Sets the Queue storage entity name.
   *
   * @param string $name
   *   The Queue storage entity name.
   *
   * @return \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface
   *   The called Queue storage entity entity.
   */
  public function setName($name): QueueStorageEntityInterface;

  /**
   * Gets the Queue storage entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Queue storage entity.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Queue storage entity creation timestamp.
   *
   * @param int $timestamp
   *   The Queue storage entity creation timestamp.
   *
   * @return \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface
   *   The called Queue storage entity entity.
   */
  public function setCreatedTime($timestamp): QueueStorageEntityInterface;

  /**
   * Get the status of the entity.
   *
   * @return bool
   *   Status of the entity.
   */
  public function isFinished(): bool;

  /**
   * Sets the Queue storage entity status.
   *
   * @param bool $status
   *   The Queue storage entity status.
   *
   * @return \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface
   *   The called Queue storage entity entity.
   */
  public function setFinished($status): QueueStorageEntityInterface;

}
