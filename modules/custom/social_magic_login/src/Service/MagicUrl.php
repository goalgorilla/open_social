<?php

namespace Drupal\social_magic_login\Service;

use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Service to generate one-time login links (a.k.a 'magic' #lama's).
 */
class MagicUrl implements MagicUrlInterface {

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
   * {@inheritdoc}
   */
  public function create(UserInterface $account, string $destination, array $options) : ?Url {
    if (!isset($account, $destination)) {
      return NULL;
    }

    // Check if path isn't external.
    if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($destination)) {
      return NULL;
    }

    // Get url options and prerequisites.
    $timestamp = \Drupal::time()->getRequestTime();
    $lang_code = $options['langcode'] ?? $account->getPreferredLangcode();
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
