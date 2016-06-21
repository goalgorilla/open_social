<?php

namespace Drupal\social_event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event enrollment entities.
 *
 * @ingroup social_event
 */
interface EventEnrollmentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Event enrollment name.
   *
   * @return string
   *   Name of the Event enrollment.
   */
  public function getName();

  /**
   * Sets the Event enrollment name.
   *
   * @param string $name
   *   The Event enrollment name.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setName($name);

  /**
   * Gets the Event enrollment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event enrollment.
   */
  public function getCreatedTime();

  /**
   * Sets the Event enrollment creation timestamp.
   *
   * @param int $timestamp
   *   The Event enrollment creation timestamp.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event enrollment published status indicator.
   *
   * Unpublished Event enrollment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event enrollment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event enrollment.
   *
   * @param bool $published
   *   TRUE to set this Event enrollment to published, FALSE for unpublished.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setPublished($published);

}
