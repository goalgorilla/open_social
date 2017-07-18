<?php

namespace Drupal\social_post\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Post entities.
 *
 * @ingroup social_post
 */
interface PostInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Returns the post type.
   *
   * @return string
   *   The post type name.
   */
  public function getType();

  /**
   * Sets the post type.
   *
   * @param string $type
   *   The post type.
   *
   * @return $this
   */
  public function setType($type);

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
   * @return \Drupal\social_post\Entity\PostInterface
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
   * @return \Drupal\social_post\Entity\PostInterface
   *   The called Post entity.
   */
  public function setPublished($published);

}
