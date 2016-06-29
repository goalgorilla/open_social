<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Storage\GroupContentStorage.
 */

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines the storage handler class for group content entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group content entities based on group and plugin information.
 */
class GroupContentStorage extends SqlContentEntityStorage implements GroupContentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $content_enabler = NULL, $filters = []) {
    $properties = ['gid' => $group->id()] + $filters;

    // If a plugin ID was provided, set the group content type ID for it.
    if (isset($content_enabler)) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin($content_enabler);
      $properties['type'] = $plugin->getContentTypeConfigId();
    }

    return $this->loadByProperties($properties);
  }

}
