<?php

namespace Drupal\search_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Represents a piece of configuration that was not permanently saved yet.
 */
interface UnsavedConfigurationInterface {

  /**
   * Sets the current user ID.
   *
   * @param int|string $current_user_id
   *   The UID of the currently logged-in user, or the session ID (for anonymous
   *   users).
   */
  public function setCurrentUserId($current_user_id);

  /**
   * Determines if there are any unsaved changes in this configuration.
   *
   * @return bool
   *   TRUE if any changes have been made to this configuration compared to the
   *   one in permanent storage; FALSE otherwise.
   */
  public function hasChanges();

  /**
   * Determines whether this configuration was saved by a different user.
   *
   * @return bool
   *   TRUE if a user not equal to the current one created this temporary
   *   configuration state and editing by the current user should therefore be
   *   forbidden.
   */
  public function isLocked();

  /**
   * Retrieves the owner of the lock on this configuration, if any.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager to use for loading the user, if necessary. Or NULL to
   *   retrieve the entity manager from the global container.
   *
   * @return \Drupal\user\UserInterface|null
   *   The lock's owner; or NULL if this object represents the still unchanged
   *   configuration that is currently stored.
   */
  public function getLockOwner(EntityTypeManagerInterface $entity_type_manager = NULL);

  /**
   * Retrieves the last updated date of this configuration, if any.
   *
   * @return int|null
   *   The time of the last change to this configuration; or NULL if this object
   *   represents the still unchanged configuration that is currently stored.
   */
  public function getLastUpdated();

  /**
   * Sets the lock information for this configuration.
   *
   * @param object|null $lock
   *   The lock information, as an object with properties "owner" and "updated";
   *   or NULL if this object represents the still unchanged configuration that
   *   is currently stored.
   *
   * @return $this
   */
  public function setLockInformation($lock = NULL);

  /**
   * Saves the changes represented by this object permanently.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager to use, if necessary. Or NULL to retrieve the entity
   *   manager from the global container.
   */
  public function savePermanent(EntityTypeManagerInterface $entity_type_manager = NULL);

  /**
   * Discards the changes represented by this object.
   */
  public function discardChanges();

}
