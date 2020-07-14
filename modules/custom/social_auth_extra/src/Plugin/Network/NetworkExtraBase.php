<?php

namespace Drupal\social_auth_extra\Plugin\Network;

use Drupal\social_auth\Plugin\Network\NetworkBase;

/**
 * Class NetworkExtraBase.
 *
 * @package Drupal\social_auth_extra\Plugin\Network
 */
abstract class NetworkExtraBase extends NetworkBase implements NetworkExtraInterface {

  use NetworkExtraTrait;

}
