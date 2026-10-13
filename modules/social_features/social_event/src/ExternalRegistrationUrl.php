<?php

declare(strict_types=1);

namespace Drupal\social_event;

use Drupal\Component\Utility\UrlHelper;

/**
 * Validates an external event registration URL.
 */
final class ExternalRegistrationUrl {

  /**
   * Checks whether a URL can be used as an external registration destination.
   */
  public static function isValid(string $url): bool {
    $url = trim($url);
    if ($url === '' || !UrlHelper::isValid($url, TRUE)) {
      return FALSE;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    return $scheme === 'http' || $scheme === 'https';
  }

}
