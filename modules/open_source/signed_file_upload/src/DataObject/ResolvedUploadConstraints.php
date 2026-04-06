<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

use Drupal\signed_file_upload\Enum\UploadValidationKind;

/**
 * Represent a set of normalized upload constraints.
 *
 * This allows the signed file upload system to represent constraints on file
 * uploads regardless of where the file will later be used within the
 * application.
 *
 * @see \Drupal\signed_file_upload\UploadConstraintResolverInterface
 */
readonly class ResolvedUploadConstraints {

  /**
   * Create new resolved upload constraints.
   *
   * @param non-empty-string $targetDirectory
   *   The directory in which the final file should be saved.
   * @param non-negative-int $maxBytes
   *   The maximum file size that can be uploaded.
   * @param string[] $allowedExtensions
   *   The list of file extensions that may be uploaded. Allowed mime types are
   *   automatically derived based on the extensions.
   * @param \Drupal\signed_file_upload\Enum\UploadValidationKind $validationKind
   *   The type of validation that will be applied.
   * @param \Drupal\signed_file_upload\DataObject\ImageDimensionBounds|null $imageDimensionBounds
   *   In case of image uploads, the maximum dimensions the image may have. NULL
   *   in case the upload is not an image.
   */
  public function __construct(
    public string $targetDirectory,
    public int $maxBytes,
    public array $allowedExtensions,
    public UploadValidationKind $validationKind,
    public ?ImageDimensionBounds $imageDimensionBounds,
  ) {}

}
