<?php

namespace Drupal\social_magic_login\Service;

use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Class MagicLoginCreate.
 */
class MagicUrlCreate {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * MagicUrlCreate constructor.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   */
  public function __construct(PathValidatorInterface $pathValidator) {
    $this->pathValidator = $pathValidator;
  }

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
   * @return \Drupal\Core\Url
   *   An url based on the magic login route.
   */
  public function create(UserInterface $account, $destination, array $options) {
    if (!isset($account, $destination)) {
      return NULL;
    }

    // Check if path isn't external.
    if (!$this->pathValidator->isValid($destination)) {
      return NULL;
    }

    // Get url options and prerequisites.
    $timestamp = \Drupal::time()->getRequestTime();
    $lang_code = isset($options['langcode']) ? $options['langcode'] : $account->getPreferredLangcode();
    $url_options = [
      'absolute' => TRUE,
      'language' => \Drupal::languageManager()->getLanguage($lang_code),
    ];

    // Create url from route with the destination if it's set.
    return Url::fromRoute('social_magic_login.login',
      [
        'uid' => $account->id(),
        'timestamp' => $timestamp,
        'hash' => user_pass_rehash($account, $timestamp),
        'destination' => base64_encode($destination),
      ],
      $url_options
    );
  }

}
