<?php

/**
 * @file
 * Contains \Drupal\social_post\PostInterface.
 */

namespace Drupal\social_post;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Post entities.
 *
 * @ingroup social_post
 */
interface PostInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Post creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Post.
   */
  public function getCreatedTime();

  /**
   * Sets the Post creation timestamp.
   *
   * @param int $timestamp
   *   The Post creation timestamp.
   *
   * @return \Drupal\social_post\PostInterface
   *   The called Post entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Post published status indicator.
   *
   * Unpublished Post are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Post is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Post.
   *
   * @param bool $published
   *   TRUE to set this Post to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\social_post\PostInterface
   *   The called Post entity.
   */
  public function setPublished($published);

}
