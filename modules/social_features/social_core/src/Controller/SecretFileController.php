<?php

declare(strict_types=1);

namespace Drupal\social_core\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\Entity\ImageStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

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
   * The lock backend.
   */
  protected LockBackendInterface $lock;

  /**
   * The image factory.
   */
  protected ImageFactory $imageFactory;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * FileDownloadController constructor.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal time service.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   */
  public function __construct(
    StreamWrapperManagerInterface $stream_wrapper_manager,
    TimeInterface $time,
    LockBackendInterface $lock,
    ImageFactory $image_factory
  ) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->time = $time;
    $this->lock = $lock;
    $this->imageFactory = $image_factory;
    $this->logger = $this->getLogger('image');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('stream_wrapper_manager'),
      $container->get("datetime.time"),
      $container->get('lock'),
      $container->get('image.factory')
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

    // For some reason Drupal core is incorrect in how it invokes our
    // SecretFiles path processor which can cause the 'filepath' attribute to be
    // unset, in that case we just manually try to find it again here.
    // Once Drupal finally allows arbitrary length arguments like Symfony does
    // we can remove our PathProcessor and our workaround here and things should
    // be reliably handled by Symfony for us.
    if ($target === NULL) {
      $path = $request->getPathInfo();
      $prefix = "/system/file/$hash/$expires_at/";
      if (!str_starts_with($path, $prefix)) {
        throw new NotFoundHttpException();
      }

      $target = substr($path, strlen($prefix));
      if ($target === '') {
        throw new NotFoundHttpException();
      }
    }

    // Merge remaining path arguments into relative file path.
    $uri = 'secret://' . $target;

    // The secure hash may not be tampered with to prove the other variables in
    // the URL are valid.
    if (!self::confirmHash($expires_at, $target, $hash)) {
      throw new NotFoundHttpException();
    }

    // The link must not have expired.
    // @todo Since Drupal does not support adding cache metadata from
    // StreamWrappers it can currently occur that we cache a link in a render
    // cache longer than the link is valid which would cause viewers to be
    // provided with broken links. To avoid this we currently just assume that
    // a link with a valid hash is secret enough and thus always valid for some
    // time in the future. There is no current path to adding cache context in
    // StreamWrappers so there's no single issue to point to, but this is a
    // combination of various cache API and file system improvements that
    // people are discussing. Once fixed, uncomment the next line and remove
    // the one after.
    // $time_left = $expires_at - $this->time->getRequestTime();
    $time_left = Settings::get("secret_file_bucket_time", 3600 /* = 1 hour */);
    if ($time_left <= 0) {
      throw new NotFoundHttpException();
    }

    if ($this->streamWrapperManager->isValidScheme("secret")) {
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

      if (is_file($uri)) {
        return new BinaryFileResponse(
          $uri,
          200,
          $headers
        );
      }

      // We need a fall-back for image styles which only get created on-demand.
      // When they exist they'll be handled by the previous block.
      if (str_starts_with($target, "styles/")) {
        // URIs for image styles are in the form of:
        // /styles/{image_style}/{scheme}/{file}
        // if we can't get the image style from the URL or we're passed on with
        // an incorrect scheme then we abort.
        $matches = [];
        if (!preg_match("|styles/(\w+)/secret/(.+)|", $target, $matches)) {
          throw new NotFoundHttpException();
        }
        [$_, $image_style, $source_target] = $matches;
        $image_uri = "secret://$source_target";

        // Normally the ImageStyleDownloadController requires a token to be
        // verified to generate a derivative to prevent DoS attacks through
        // derivative generation. However, our tamper-proof URLs for the secret
        // file system already prevent this allowing us to just start generating
        // the file knowing that it doesn't exist.
        $image_style = ImageStyle::load($image_style);
        if ($image_style === NULL) {
          throw new NotFoundHttpException();
        }

        $derivative_uri = $image_style->buildUri($image_uri);

        if (!file_exists($image_uri)) {
          // If the image style converted the extension, it has been added to
          // the original file, resulting in filenames like image.png.jpeg. So
          // to find the actual source image, we remove the extension and check
          // if that image exists.
          /** @var string|false $filepath */
          $filepath = StreamWrapperManager::getTarget($image_uri);
          if ($filepath === FALSE) {
            throw new NotFoundHttpException();
          }
          $path_info = pathinfo($filepath);
          $converted_image_uri = sprintf('secret://%s%s', ($path_info['dirname'] ?? '.') === '.' ? '' : $path_info['dirname'] . DIRECTORY_SEPARATOR, $path_info['filename']);
          if (!file_exists($converted_image_uri)) {
            $this->logger->notice(
              'Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.',
              [
                '%source_image_path' => $image_uri,
                '%derivative_path' => $derivative_uri,
              ]
            );
            throw new NotFoundHttpException((string) $this->t('Error generating image, missing source file.'));
          }
          else {
            // The converted file does exist, use it as the source.
            $image_uri = $converted_image_uri;
          }
        }

        // Don't start generating the image if the derivative already exists or
        // if generation is in progress in another thread.
        if (!file_exists($derivative_uri)) {
          $lock_name = 'image_style_deliver:' . $image_style->id() . ':' . Crypt::hashBase64($image_uri);
          $lock_acquired = $this->lock->acquire($lock_name);

          if (!$lock_acquired) {
            // Tell client to retry again in 3 seconds. Currently no browsers
            // are known to support Retry-After.
            throw new ServiceUnavailableHttpException(3, 'Image generation in progress. Try again shortly.');
          }
        }

        // Try to generate the image, unless another thread just did it while we
        // were acquiring the lock.
        $success = file_exists($derivative_uri) || $image_style->createDerivative($image_uri, $derivative_uri);

        if (!empty($lock_acquired)) {
          assert(isset($lock_name), 'A lock may not be acquired without setting "$lock_name"');
          $this->lock->release($lock_name);
        }

        if (!$success) {
          $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
          throw new HttpException(500, (string) $this->t('Error generating image.'));
        }

        $image = $this->imageFactory->get($derivative_uri);
        $uri = $image->getSource();
        $headers['Content-Type'] = $image->getMimeType();
        $headers['Content-Length'] = $image->getFileSize();
        // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
        // sets response as not cacheable if the Cache-Control header is not
        // already modified. When $is_public is TRUE, the following sets the
        // Cache-Control header to "public".
        return new BinaryFileResponse(
          $uri,
          200,
          $headers
        );
      }
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
