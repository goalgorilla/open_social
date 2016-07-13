<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Storage\GroupContentStorageInterface.
 */

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for group content entity storage classes.
 */
interface GroupContentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Retrieves all GroupContent entities for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   * @param string $content_enabler
   *   (optional) A content enabler plugin ID to filter on.
   * @param array $filters
   *   (optional) An associative array of extra filters where the keys are
   *   property or field names and the values are the value to filter on.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities matching the criteria.
   */
  public function loadByGroup(GroupInterface $group, $content_enabler = NULL, $filters = []);

}
