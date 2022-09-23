<?php

namespace Drupal\image_optimization\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image_optimization\AsymmetricCryptTrait;
use Drupal\image_optimization\ImageDerivativeGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Defines a controller to serve image transformed.
 */
class ImageController extends ControllerBase {

  use AsymmetricCryptTrait;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected ImageFactory $imageFactory;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The private key for decryption.
   *
   * @var string
   */
  protected string $privateKey;

  /**
   * Constructs an ImageStyleDownloadController object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\File\FileSystemInterface|null $file_system
   *   The system service.
   *
   * @throws \Exception
   *   WHen the private key is not available.
   */
  public function __construct(
    LockBackendInterface $lock,
    ImageFactory $image_factory,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system = NULL
  ) {
    $this->lock = $lock;
    $this->imageFactory = $image_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $this->getLogger('image');
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;

    if (!isset($file_system)) {
      @trigger_error('Not defining the $file_system argument to ' . __METHOD__ . ' is deprecated in drupal:9.1.0 and will throw an error in drupal:10.0.0.', E_USER_DEPRECATED);
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
    $this->privateKey = $this->getPrivateKey();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('lock'),
      $container->get('image.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  /**
   * Generates a derivative, given encrypted arguments.
   *
   * After generating an image, transfer it to the requesting agent.
   *
   * @param string $path
   *   The encrypted path.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the file request is invalid.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   Thrown when the file is still being generated.
   */
  public function deliver(string $path) {
    $encrypted_arguments = pathinfo($path, PATHINFO_FILENAME);
    $arguments = $this->decrypt($encrypted_arguments, $this->privateKey);
    if ($arguments === NULL) {
      throw new NotFoundHttpException();
    }
    parse_str($arguments, $query);

    $file_storage = $this->entityTypeManager->getStorage('file');
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $file_storage->loadByProperties([
      'uuid' => $query['uuid'],
    ]);
    if (empty($files)) {
      throw new NotFoundHttpException();
    }
    $file = reset($files);

    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($extension, $this->imageFactory->getSupportedExtensions())) {
      throw new NotFoundHttpException();
    }

    $image = $this->imageFactory->get($file->getFileUri());
    $derivative_image = new ImageDerivativeGenerator($image);

    if (isset($query['width'], $query['height'], $query['fit'])) {
      $derivative_image->transformDimensions($query['width'], $query['height'], $query['fit']);
    }
    if (isset($query['extension'])) {
      $derivative_image->transformExtension($query['extension']);
    }

    $headers = [];
    $derivative_uri = "{$arguments}";
    $destination_uri = $derivative_image->getDestinationUri($path);

    // Don't start generating the image if the derivative already exists or if
    // generation is in progress in another thread.
    if (!file_exists($destination_uri)) {
      $lock_name = 'image_optimization_deliver:' . Crypt::hashBase64($destination_uri);
      $lock_acquired = $this->lock->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, 'Image generation in progress. Try again shortly.');
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = file_exists($destination_uri) || $derivative_image->generate($path);

    if (!empty($lock_acquired)) {
      $this->lock->release($lock_name);
    }

    if ($success) {
      $image = $derivative_image->getImage();
      $headers += [
        'Content-Type' => $image->getMimeType(),
        'Content-Length' => $image->getFileSize(),
      ];
      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. When $is_public is TRUE, the following sets the
      // Cache-Control header to "public".
      return new BinaryFileResponse($destination_uri, 200, $headers);
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Get the private key.
   *
   * @return string
   *   Returns the public key.
   *
   * @throws \Exception
   */
  protected function getPrivateKey(): string {
    $public_key_path = $this->config('image_optimization.settings')->get('private_key');
    $file_path = $this->fileSystem->realpath($public_key_path) ?: $public_key_path;
    $key = file_get_contents($file_path);

    if (!$key) {
      throw new \Exception('The private key is not available.');
    }

    return $key;
  }

}
