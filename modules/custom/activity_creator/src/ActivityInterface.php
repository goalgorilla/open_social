<?php

namespace Drupal\activity_creator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Activity entities.
 *
 * @ingroup activity_creator
 */
interface ActivityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Activity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Activity.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Activity creation timestamp.
   *
   * @param int $timestamp
   *   The Activity creation timestamp.
   *
   * @return \Drupal\activity_creator\ActivityInterface
   *   The called Activity entity.
   */
  public function setCreatedTime(int $timestamp): ActivityInterface;

  /**
   * Returns the Activity published status indicator.
   *
   * Unpublished Activity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Activity is published.
   */
  public function isPublished(): bool;

  /**
   * Sets the published status of a Activity.
   *
   * @param bool $published
   *   TRUE to set this Activity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\activity_creator\ActivityInterface
   *   The called Activity entity.
   */
  public function setPublished(bool $published): ActivityInterface;

  /**
   * Get related entity url.
   *
   * @return \Drupal\Core\Url|string
   *   Empty string if entity canonical url could not be found.
   */
  public function getRelatedEntityUrl(): string|\Drupal\Core\Url;

  /**
   * Get destinations.
   *
   * @return array
   *   The list of destinations.
   */
  public function getDestinations(): array;

}
