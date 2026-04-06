<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\Enum\UploadValidationKind;
use Drupal\signed_file_upload\Exception\InvalidContentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * The Upload validator.
 *
 * Responsible for validating the files being uploaded at every stage of the
 * process. Contains methods for the initial intent, the intermediate upload
 * stages and the file finalization.
 */
class UploadValidator implements UploadValidatorInterface {

  /**
   * The maximum file name length.
   *
   * @see Drupal\file\Plugin\Validation\Constraint\FileNameLengthConstraint::$maxLength.
   */
  protected int $maxLength = 240;

  /**
   * The Symfony mime type guesser.
   *
   * This is not available in Drupal directly. The default Symfony mime type
   * guesser registers two guessers by default which rely on finfo or the system
   * file utility. These look at the binary data and ignore file extensions.
   *
   * @return \Symfony\Component\Mime\MimeTypesInterface
   */
  protected readonly MimeTypesInterface $systemMimeTypeGuesser;

  /**
   * Create a new validator service instance.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $extensionMimeTypeGuesser
   *   Drupal's extension based mimetype guesser.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The Drupal image factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for this module.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    protected readonly FileSystemInterface $fileSystem,
    protected readonly MimeTypeGuesserInterface $extensionMimeTypeGuesser,
    protected readonly ImageFactory $imageFactory,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->systemMimeTypeGuesser = new MimeTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function assertUploadIntent(ResolvedUploadConstraints $constraints, UploadMetadata $metadata, ?int $uploadLength): void {
    if (str_contains($metadata->filename, '/') || str_contains($metadata->filename, '\\')) {
      throw new InvalidContentException("Filename may not contain directory separators.");
    }

    if (mb_strlen($metadata->filename) > $this->maxLength) {
      throw new InvalidContentException(sprintf(
        "The file's name exceeds the %d characters limit. Rename the file and try again.",
        $this->maxLength,
      ));
    }

    if ($metadata->getFileExtension() === NULL) {
      throw new InvalidContentException("Filename must have an extension.");
    }

    $allowInsecureUploads = $this->configFactory->get('system.file')->get('allow_insecure_uploads');
    if (!$allowInsecureUploads && preg_match(FileSystemInterface::INSECURE_EXTENSION_REGEX, $metadata->filename)) {
      throw new InvalidContentException("File has invalid extension.");
    }

    $allowed = array_map('strtolower', $constraints->allowedExtensions);
    if (!in_array($metadata->getFileExtension(), $allowed, TRUE)) {
      throw new InvalidContentException("File has invalid extension, must be one of " . implode(", ", $allowed));
    }

    if ($uploadLength !== NULL && $constraints->maxBytes > 0 && $uploadLength > $constraints->maxBytes) {
      throw new InvalidContentException(sprintf("Requested upload length %d is larger than maximum for target %d", $uploadLength, $constraints->maxBytes));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateArtifactContentSignature(UploadSession $session, int $fileSize): bool {
    $ext = $session->metadata->getFileExtension();
    if ($ext === NULL || !$this->requiresMagic($ext)) {
      return TRUE;
    }

    $min = $this->minimumHeaderLength($ext);
    if ($fileSize < $min) {
      return TRUE;
    }

    $path = $this->fileSystem->realpath($session->artifactUri);
    if ($path === FALSE) {
      throw new \RuntimeException(sprintf('Could not resolve staged upload path for %s.', $session->artifactUri));
    }

    $handle = fopen($path, 'rb');
    if ($handle === FALSE) {
      throw new \RuntimeException(sprintf('Could not read staged upload bytes at %s.', $path));
    }

    $header = fread($handle, 512);
    fclose($handle);
    if ($header === FALSE) {
      throw new \RuntimeException(sprintf('Could not read staged upload header from %s.', $path));
    }

    return $this->headerMatchesExtension($ext, $header);
  }

  /**
   * {@inheritdoc}
   */
  public function assertStagedFile(ResolvedUploadConstraints $constraints, UploadSession $session): void {
    $path = $this->fileSystem->realpath($session->artifactUri);
    if ($path === FALSE) {
      throw new \RuntimeException(sprintf('Could not resolve path %s.', $session->artifactUri));
    }

    $size = $this->getSize($path);
    if ($size === 0) {
      return;
    }

    if ($constraints->maxBytes !== 0 && $size > $constraints->maxBytes) {
      $this->logger->error(
        "Staged file of size {size} exceeded maximum of {maximum} bytes which should've been prevented by the patch handler, this indicates a bug or tampering.",
        ['size' => $size, 'maximum' => $constraints->maxBytes],
      );
      throw new InvalidContentException("File exceeded maximum filesize of $constraints->maxBytes bytes.");
    }

    $detectedMimeType = $this->systemMimeTypeGuesser->guessMimeType($path);
    $extensionMimeType = $this->extensionMimeTypeGuesser->guessMimeType($session->metadata->filename);

    if ($detectedMimeType !== $extensionMimeType) {
      throw new InvalidContentException(sprintf(
        "Detected mime type '%s' does not match expected mime type '%s' for extension '.%s'.",
        $detectedMimeType,
        $extensionMimeType,
        $session->metadata->getFileExtension(),
      ));
    }

    match ($constraints->validationKind) {
      UploadValidationKind::FileField => NULL,
      UploadValidationKind::ImageField, UploadValidationKind::EditorInlineImage => $this->assertStagedImage($constraints, $session, $path),
    };
  }

  /**
   * Get the size for a file at a specific path.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return int
   *   The size in bytes.
   */
  protected function getSize(string $path) : int {
    $size = filesize($path);
    if ($size === FALSE) {
      throw new \RuntimeException(sprintf('Could not read staged upload size for %s.', $path));
    }

    return $size;
  }

  /**
   * Run image specific validations for a staged file (ready to be finalized).
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   The upload constraints for this image.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The upload session we're validating.
   * @param string $path
   *   The resolved (realpath) path we're validating.
   *
   * @throws \Drupal\signed_file_upload\Exception\InvalidContentException
   *   In case a requirement is unmet.
   */
  protected function assertStagedImage(ResolvedUploadConstraints $constraints, UploadSession $session, string $path) : void {
    $image = $this->imageFactory->get($path);
    if (!$image->isValid()) {
      throw new InvalidContentException("Uploaded image is not a valid image file.");
    }

    if ($constraints->imageDimensionBounds?->max !== NULL) {
      if ($image->getWidth() > $constraints->imageDimensionBounds->max->width || $image->getHeight() > $constraints->imageDimensionBounds->max->height) {
        throw new InvalidContentException("Image dimensions are too large, must be smaller than {$constraints->imageDimensionBounds->max->width}x{$constraints->imageDimensionBounds->max->height}.");
      }
    }

    if ($constraints->imageDimensionBounds?->min !== NULL) {
      if ($image->getWidth() < $constraints->imageDimensionBounds->min->width || $image->getHeight() < $constraints->imageDimensionBounds->min->height) {
        throw new InvalidContentException("Image dimensions are too small, must be larger than {$constraints->imageDimensionBounds->min->width}x{$constraints->imageDimensionBounds->min->height}.");
      }
    }
  }

  /**
   * Whether the extension uses a known magic-byte check.
   *
   * @param lowercase-string $extension
   *   The file extension.
   *
   * @return bool
   *   Whether it uses a known magic-byte check.
   */
  protected function requiresMagic(string $extension): bool {
    return match ($extension) {
      'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Minimum bytes needed on disk before the signature can be evaluated.
   *
   * @param lowercase-string $extension
   *   The file extension.
   *
   * @return int
   *   The minimum amount of bytes needed to validate the header.
   */
  protected function minimumHeaderLength(string $extension): int {
    return match ($extension) {
      'jpg', 'jpeg' => 3,
      'png' => 8,
      'gif' => 6,
      'webp' => 12,
      'pdf' => 4,
      default => 0,
    };
  }

  /**
   * Whether the read file header matches the extension.
   *
   * @param lowercase-string $extension
   *   The file extension.
   * @param string $header
   *   The header to check.
   *
   * @return bool
   *   Whether the header matches the file extension.
   */
  protected function headerMatchesExtension(string $extension, string $header) : bool {
    return match ($extension) {
      'jpg', 'jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
      'png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
      'gif' => str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a'),
      'webp' => strlen($header) >= 12
        && str_starts_with($header, 'RIFF')
        && substr($header, 8, 4) === 'WEBP',
      'pdf' => str_starts_with($header, '%PDF'),
      default => TRUE,
    };
  }

}
