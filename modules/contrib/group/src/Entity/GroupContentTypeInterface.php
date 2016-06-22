<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group content type entity.
 */
interface GroupContentTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this group content type.
   */
  public function getDescription();

  /**
   * Gets the group type the content type was created for.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The group type for which the content type was created.
   */
  public function getGroupType();

  /**
   * Gets the group type ID the content type was created for.
   *
   * @return string
   *   The group type ID for which the content type was created.
   */
  public function getGroupTypeId();

  /**
   * Gets the content enabler plugin the content type uses.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The content enabler plugin the content type uses.
   */
  public function getContentPlugin();

  /**
   * Gets the content enabler plugin ID the content type uses.
   *
   * @return string
   *   The content enabler plugin ID the content type uses.
   */
  public function getContentPluginId();

  /**
   * Loads group content type entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content type entities indexed by their IDs.
   */
  public static function loadByContentPluginId($plugin_id);

}
