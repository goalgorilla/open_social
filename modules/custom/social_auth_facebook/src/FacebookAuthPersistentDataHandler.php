<?php

namespace Drupal\social_auth_facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Drupal\social_auth_extra\AuthSessionDataHandler;

/**
 * Variables are written to and read from session via this class.
 *
 * By default, Facebook SDK uses native PHP sessions for storing data. We
 * implement Facebook\PersistentData\PersistentDataInterface using Symfony
 * Sessions so that Facebook SDK will use that instead of native PHP sessions.
 * Also SimpleFbConnect reads data from and writes data to session via this
 * class.
 *
 * @see https://developers.facebook.com/docs/php/PersistentDataInterface/5.0.0
 */
class FacebookAuthPersistentDataHandler extends AuthSessionDataHandler implements PersistentDataInterface {

}
