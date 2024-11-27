<?php

namespace Drupal\social_group_default_route;

use Drupal\group\Entity\GroupInterface;

/**
 * Manages discovery and instantiation of group landing tab plugins.
 *
 * Group landing tabs are links that used as default pages of Group entity
 * depends on Group membership.
 */
interface GroupLandingTabManagerInterface {

  /**
   * The membership - member.
   */
  const MEMBER = 'member';

  /**
   * The membership - non-member.
   */
  const NON_MEMBER = 'non-member';

  /**
   * The membership - all.
   */
  const ALL = 'all';

  /**
   * Get available landing tabs.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param string $type
   *   The tab type by group membership:
   *    - GroupLandingTabManagerInterface:MEMBER;
   *    - GroupLandingTabManagerInterface:NON_MEMBER;
   *    - GroupLandingTabManagerInterface:ALL.
   * @param array $field_values
   *   The array of group field valued.
   *
   * @return array
   *   The array of tabs.
   */
  public function getAvailableLendingTabs(GroupInterface $group, string $type, array $field_values = []): array;

  /**
   * Get group tab management conditions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return array
   *   The array of conditions.
   */
  public function getGroupManagementTabConditions(GroupInterface $group): array;

}
