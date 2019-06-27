<?php

namespace Drupal\social_magic_login\Service;

use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Interface that allows the generation of one-time login links.
 */
interface MagicUrlInterface {

  /**
   * Create a magic login link.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param string $destination
   *   The uri of the final destination.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *    URLs. If langcode is NULL the users preferred language is used.
   *
   * @return \Drupal\Core\Url|null
   *   An url based on the magic login route.
   */
  public function create(UserInterface $account, string $destination, array $options) : ?Url;

}
