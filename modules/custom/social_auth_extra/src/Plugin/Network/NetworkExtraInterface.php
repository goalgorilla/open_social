<?php

namespace Drupal\social_auth_extra\Plugin\Network;

use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Interface NetworkExtraInterface.
 *
 * @package Drupal\social_auth_extra\Plugin\Network
 */
interface NetworkExtraInterface extends NetworkInterface {

  /**
   * Returns status of social network.
   *
   * @return bool
   *   The status of the social network.
   */
  public function isActive();

  /**
   * Returns key-name of a social network.
   *
   * @return string
   *   The key-name of a social network.
   */
  public function getSocialNetworkKey();

}
