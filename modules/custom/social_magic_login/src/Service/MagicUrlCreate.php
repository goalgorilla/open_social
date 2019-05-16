<?php

namespace Drupal\social_magic_login\Service;

use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Class MagicLoginCreate.
 */
class MagicUrlCreate {

  /**
   * Create a magic login link.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *    URLs. If langcode is NULL the users preferred language is used.
   *   - destination: A redirect destination.
   *    If destination is NULL it's not added.
   *
   * @return \Drupal\Core\Url
   */
  public function create(UserInterface $account, array $options) {
    // Get url options and prerequisites.
    $timestamp = \Drupal::time()->getRequestTime();
    $lang_code = isset($options['langcode']) ? $options['langcode'] : $account->getPreferredLangcode();
    $url_options = [
      'absolute' => TRUE,
      'language' => \Drupal::languageManager()->getLanguage($lang_code),
    ];

    // Add a destination if it's set.
    if (NULL !== $options['destination']) {
      $url_options['query']['destination'] = $options['destination'];
    }

    // Create url from route with the destination if it's set.
    return Url::fromRoute('social_magic_login.login',
      [
        'uid' => $account->id(),
        'timestamp' => $timestamp,
        'hash' => user_pass_rehash($account, $timestamp),
      ],
      $url_options
    );
  }

}
