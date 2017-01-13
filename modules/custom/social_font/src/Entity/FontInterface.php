<?php

namespace Drupal\social_font\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Font entities.
 *
 * @ingroup social_font
 */
interface FontInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Font name.
   *
   * @return string
   *   Name of the Font.
   */
  public function getName();

  /**
   * Sets the Font name.
   *
   * @param string $name
   *   The Font name.
   *
   * @return \Drupal\social_font\Entity\FontInterface
   *   The called Font entity.
   */
  public function setName($name);

  /**
   * Gets the Font creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Font.
   */
  public function getCreatedTime();

  /**
   * Sets the Font creation timestamp.
   *
   * @param int $timestamp
   *   The Font creation timestamp.
   *
   * @return \Drupal\social_font\Entity\FontInterface
   *   The called Font entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Font published status indicator.
   *
   * Unpublished Font are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Font is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Font.
   *
   * @param bool $published
   *   TRUE to set this Font to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\social_font\Entity\FontInterface
   *   The called Font entity.
   */
  public function setPublished($published);

}
