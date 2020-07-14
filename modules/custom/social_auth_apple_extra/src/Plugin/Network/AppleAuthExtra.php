<?php

namespace Drupal\social_auth_apple_extra\Plugin\Network;

use Drupal\social_auth_apple\Plugin\Network\AppleAuth;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraTrait;

/**
 * Class AppleAuthExtra.
 *
 * @package Drupal\social_auth_apple_extra\Plugin\Network
 */
class AppleAuthExtra extends AppleAuth implements NetworkExtraInterface {

  use NetworkExtraTrait;

}
