<?php

declare(strict_types=1);

namespace Drupal\social_core\StreamWrapper;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\social_core\Controller\SecretFileController;

/**
 * Open Social's secret (secret://) stream wrapper class.
 *
 * Provides support for storing privately accessible files but does so through a
 * secret time-limited URL that can be cached for the time limit.
 */
class SecretStream extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_NORMAL;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getName() {
    return t('Secret files');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDescription() {
    return t('Secret local files served by Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    // We re-use the private file folder for now. This makes switching how we
    // serve files easier.
    return Settings::get('file_private_path');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $target = $this->getTarget();
    assert(is_string($target), "Broken implementation of LocalStream.");
    $path = str_replace('\\', '/', $target);

    $current = \Drupal::time()->getRequestTime();
    $lifetime = Settings::get("secret_file_bucket_time", 3600 /* = 1 hour */);
    $expires_at = (string) self::getExpiresAt($current, $lifetime);

    $hash = SecretFileController::createHash($expires_at, $path);

    // Leak the max age we calculate for images so we can set it in
    // SecretResponseCacheSubscriber until Drupal supports propagating
    // cache-ability metadata.
    $request = \Drupal::request();
    if (!$request->attributes->has('secret_file.max_age')) {
      $request->attributes->set('secret_file.max_age', (int) $expires_at - $current);
    }

    return Url::fromRoute(
      'social_core.secret_file_download',
      [
        'hash' => $hash,
        'expires_at' => $expires_at,
        'filepath' => $path,
      ],
      ['absolute' => TRUE, 'path_processing' => FALSE]
    )->toString();
  }

  /**
   * Calculate the expiry timestamp for a secret file URL.
   *
   * This function will bucket URLs into buckets that roll-over every
   * $bucket_length number of seconds. While doing so it ensures that the URL
   * is valid for at least 0.5 the bucket time and at most 1.5 times the bucket
   * size. This ensures that we don't generate URLs that immediately expire and
   * require recreation.
   *
   * @param int $current
   *   The UNIX timestamp for the current time.
   * @param int $bucket_length
   *   The size of the URL buckets in seconds. Smaller numbers create URLs that
   *   expire faster and thus will have to generate more unique URLs for the
   *   same resource. Larger number increase the chance of a leaked URL being
   *   shared with users who shouldn't have access before they URL expires.
   *
   * @return int
   *   The UNIX timestamp at which the created URL should expire.
   */
  private static function getExpiresAt(int $current, int $bucket_length) : int {
    $previous = $current - ($current % $bucket_length);
    $next = $previous + $bucket_length;
    return ($next - $current) >= ($bucket_length / 2) ? $next : ($next + $bucket_length);
  }

}
