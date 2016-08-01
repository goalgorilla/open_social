<?php

namespace Drupal\address\Entity;

use Drupal\address\Plugin\ZoneMember\ZoneMemberInterface;
use CommerceGuys\Zone\Model\ZoneInterface as ExternalZoneInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for zones.
 *
 * The external zone interface contains getters, while this interface adds
 * matching setters.
 *
 * @see \CommerceGuys\Zone\Model\ZoneInterface
 */
interface ZoneInterface extends ExternalZoneInterface, ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Sets the zone name.
   *
   * @param string $name
   *   The zone name.
   */
  public function setName($name);

  /**
   * Sets the zone scope.
   *
   * @param string $scope
   *   The zone scope.
   */
  public function setScope($scope);

  /**
   * Sets the zone priority.
   *
   * @param int $priority
   *   The zone priority.
   */
  public function setPriority($priority);

  /**
   * Sets the zone members.
   *
   * @param \Drupal\address\Plugin\ZoneMember\ZoneMemberInterface[] $members
   *   The zone members.
   */
  public function setMembers($members);

  /**
   * Adds a zone member.
   *
   * @param \Drupal\address\Plugin\ZoneMember\ZoneMemberInterface $member
   *   The zone member.
   */
  public function addMember(ZoneMemberInterface $member);

  /**
   * Removes a zone member.
   *
   * @param \Drupal\address\Plugin\ZoneMember\ZoneMemberInterface $member
   *   The zone member.
   */
  public function removeMember(ZoneMemberInterface $member);

  /**
   * Checks whether the zone has a zone member.
   *
   * @param \Drupal\address\Plugin\ZoneMember\ZoneMemberInterface $member
   *   The zone member.
   *
   * @return bool TRUE if the zone member was found, FALSE otherwise.
   */
  public function hasMember(ZoneMemberInterface $member);

}
