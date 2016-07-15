<?php

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Zone\Model\ZoneMemberInterface as ExternalZoneMemberInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines the interface for zone members.
 */
interface ZoneMemberInterface extends ExternalZoneMemberInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Gets the zone member weight.
   *
   * @return string The zone member weight.
   */
  public function getWeight();

}
