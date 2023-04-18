<?php

declare(strict_types=1);

namespace Drupal\social_core\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles serving of requests using the secret file system.
 */
class SecretFileController extends ControllerBase {

  /**
   * The stream wrapper manager.
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * The Drupal time service.
   */
  protected TimeInterface $time;

  /**
   * FileDownloadController constructor.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal time service.
   */
  public function __construct(
    StreamWrapperManagerInterface $stream_wrapper_manager,
    TimeInterface $time
  ) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('stream_wrapper_manager'),
      $container->get("datetime.time"),
    );
  }

  /**
   * Handles secret file transfers.
   *
   * We assume that if someone has a valid link then they can access the file
   * that's being served by that link. The generators of the link should provide
   * sufficient access checking.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the requested file does not exist or the link is invalid.
   */
  public function download(Request $request) : BinaryFileResponse {
    $hash = $request->attributes->get('hash');
    $expires_at = $request->attributes->get('expires_at');
    $target = $request->attributes->get('filepath');
    // Merge remaining path arguments into relative file path.
    $uri = 'secret://' . $target;

    // The secure hash may not be tampered with to prove the other variables in
    // the URL are valid.
    if (!self::confirmHash($expires_at, $target, $hash)) {
      throw new NotFoundHttpException();
    }

    // The link must not have expired.
    $time_left = $expires_at - $this->time->getRequestTime();
    if ($time_left <= 0) {
      throw new NotFoundHttpException();
    }

    if ($this->streamWrapperManager->isValidScheme("secret") && is_file($uri)) {
      // For details see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control.
      // Our image file on our secret URL can be cached by any intermediaries
      // from now until the link expiration time (`max-age`). After that time
      // intermediaries may not serve stale responses (i.e. `must-revalidate`).
      // If we could guarantee that files on disk wouldn't be replaced then we
      // could add `immutable` here too to prevent some browser requests to our
      // CDN, but our stream wrapper does not enforce that.
      $headers = [
        "Cache-Control" => "max-age=$time_left, must-revalidate",
      ];

      return new BinaryFileResponse(
        $uri,
        200,
        $headers
      );
    }

    throw new NotFoundHttpException();
  }

  /**
   * Validate a hash against the parameters that it allegedly contains.
   *
   * @param string $expires_at
   *   The UNIX timestamp at which the URL expires.
   * @param string $path
   *   The path to the secret file.
   * @param string $hash
   *   The provided hash to be validated.
   *
   * @return bool
   *   Whether the hash matches the data it contains.
   */
  protected static function confirmHash(string $expires_at, string $path, string $hash) : bool {
    return $hash === self::createHash($expires_at, $path);
  }

  /**
   * Generate a hash to be used in a secret file URL.
   *
   * @param string $expires_at
   *   The UNIX timestamp at which the URL expires.
   * @param string $path
   *   The path to the secret file.
   *
   * @return string
   *   A bash64 encoded hash.
   */
  public static function createHash(string $expires_at, string $path) : string {
    return Crypt::hmacBase64(serialize([$expires_at, $path]), Settings::getHashSalt() . "secret_files");
  }

}
