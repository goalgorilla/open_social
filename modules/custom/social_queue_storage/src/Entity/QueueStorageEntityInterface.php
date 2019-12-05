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
  public function getType();

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
  public function getName();

  /**
   * Sets the Queue storage entity name.
   *
   * @param string $name
   *   The Queue storage entity name.
   *
   * @return \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface
   *   The called Queue storage entity entity.
   */
  public function setName($name);

  /**
   * Gets the Queue storage entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Queue storage entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Queue storage entity creation timestamp.
   *
   * @param int $timestamp
   *   The Queue storage entity creation timestamp.
   *
   * @return \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface
   *   The called Queue storage entity entity.
   */
  public function setCreatedTime($timestamp);

}
