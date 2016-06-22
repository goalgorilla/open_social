<?php
/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentInterface.
 */

namespace Drupal\group\Entity;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Group content entity.
 *
 * @ingroup group
 */
interface GroupContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the group content type entity the group content uses.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   */
  public function getGroupContentType();

  /**
   * Returns the group the group content belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup();

  /**
   * Returns the entity that was added as group content.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Returns the content enabler plugin that handles the group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  public function getContentPlugin();

  /**
   * Loads group content entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities indexed by their IDs.
   */
  public static function loadByContentPluginId($plugin_id);

  /**
   * Loads group content entities which reference a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities which reference the given entity.
   */
  public static function loadByEntity(ContentEntityInterface $entity);

}
