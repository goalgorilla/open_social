<?php

namespace Drupal\social_magic_login\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
  protected PathValidatorInterface $pathValidator;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * MagicUrlCreate constructor.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    PathValidatorInterface $pathValidator,
    TimeInterface $time,
    LanguageManagerInterface $language_manager
  ) {
    $this->pathValidator = $pathValidator;
    $this->time = $time;
    $this->languageManager = $language_manager;
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
    $timestamp = $this->time->getRequestTime();
    $lang_code = $options['langcode'] ?? $account->getPreferredLangcode();
    $url_options = [
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage($lang_code),
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
