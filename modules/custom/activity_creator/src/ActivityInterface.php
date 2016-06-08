<?php

/**
 * @file
 * Contains \Drupal\activity_creator\ActivityInterface.
 */

namespace Drupal\activity_creator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Activity entities.
 *
 * @ingroup activity_creator
 */
interface ActivityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Activity name.
   *
   * @return string
   *   Name of the Activity.
   */
  public function getName();

  /**
   * Sets the Activity name.
   *
   * @param string $name
   *   The Activity name.
   *
   * @return \Drupal\activity_creator\ActivityInterface
   *   The called Activity entity.
   */
  public function setName($name);

  /**
   * Gets the Activity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Activity.
   */
  public function getCreatedTime();

  /**
   * Sets the Activity creation timestamp.
   *
   * @param int $timestamp
   *   The Activity creation timestamp.
   *
   * @return \Drupal\activity_creator\ActivityInterface
   *   The called Activity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Activity published status indicator.
   *
   * Unpublished Activity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Activity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Activity.
   *
   * @param bool $published
   *   TRUE to set this Activity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\activity_creator\ActivityInterface
   *   The called Activity entity.
   */
  public function setPublished($published);

}
