<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\ImageDimension;
use Drupal\signed_file_upload\DataObject\ImageDimensionBounds;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\signed_file_upload\Enum\UploadValidationKind;
use Drupal\signed_file_upload\Exception\InvalidDestinationException;

/**
 * The default implementation for the upload constraint resolver.
 *
 * Supports entity field and editor destinations.
 */
class UploadConstraintResolver implements UploadConstraintResolverInterface {

  use FileUploadLocationTrait;

  public function __construct(
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resolve(EntityFieldUploadDestination|EditorUploadDestination $destination): ResolvedUploadConstraints {
    return match (TRUE) {
      $destination instanceof EntityFieldUploadDestination => $this->resolveEntityFieldUploadDestination($destination),
      $destination instanceof EditorUploadDestination => $this->resolveEditorUploadDestination($destination),
    };
  }

  /**
   * Resolve a field on an entity bundle as destination.
   *
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination $destination
   *   The destination description.
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   The resolved constraints.
   */
  protected function resolveEntityFieldUploadDestination(EntityFieldUploadDestination $destination) : ResolvedUploadConstraints {
    try {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($destination->entityTypeId, $destination->bundle);
    }
    catch (PluginNotFoundException $exception) {
      throw InvalidDestinationException::fromEntityFieldDestination($destination, "entity type does not exist", $exception);
    }

    if (!isset($field_definitions[$destination->fieldName])) {
      throw InvalidDestinationException::fromEntityFieldDestination($destination, "field does not exist");
    }
    $definition = $field_definitions[$destination->fieldName];

    return match ($definition->getType()) {
      'file' => $this->resolveFileFieldConstraints($definition),
      'image' => $this->resolveImageFieldConstraints($definition),
      default => throw InvalidDestinationException::fromEntityFieldDestination($destination, "unsupported type '{$definition->getType()}'"),
    };
  }

  /**
   * Resolve the constraints for a file field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   * @param \Drupal\signed_file_upload\Enum\UploadValidationKind $validationKind
   *   The type of validation to apply after uploading (file by default).
   * @param \Drupal\signed_file_upload\DataObject\ImageDimensionBounds|null $imageDimensionBounds
   *   Image dimension bounds if they are present.
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   The upload constraints.
   */
  protected function resolveFileFieldConstraints(FieldDefinitionInterface $definition, UploadValidationKind $validationKind = UploadValidationKind::FileField, ?ImageDimensionBounds $imageDimensionBounds = NULL) : ResolvedUploadConstraints {
    // Render the upload location in isolation so that we do not leak any
    // metadata. Although we may technically store the value and it may even
    // become outdated (e.g. the date of upload folders), we expect to capture
    // the upload as it is at the start, so we can safely discard metadata.
    $targetDirectory = $this->renderer->executeInRenderContext(
      new RenderContext(),
      fn () => $this->getUploadLocation($definition),
    );
    assert($targetDirectory !== '', "Target directory for {$definition->getTargetEntityTypeId()}.{$definition->getTargetBundle()}.{$definition->getName()} is empty.");
    return new ResolvedUploadConstraints(
      targetDirectory: $targetDirectory,
      maxBytes: $this->parseFileSize($definition->getSetting('max_filesize')),
      allowedExtensions:  $this->parseFileExtensions($definition->getSetting('file_extensions')),
      validationKind: $validationKind,
      imageDimensionBounds: $imageDimensionBounds,
    );
  }

  /**
   * Resolve image specific constraints.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The image field definition.
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   The resolved constraints.
   */
  protected function resolveImageFieldConstraints(FieldDefinitionInterface $definition) : ResolvedUploadConstraints {
    $max = $this->parseFileDimensions($definition->getSetting('max_resolution'));
    $min = $this->parseFileDimensions($definition->getSetting('min_resolution'));
    return $this->resolveFileFieldConstraints(
      $definition,
      UploadValidationKind::ImageField,
      $max !== NULL || $min !== NULL ? new ImageDimensionBounds($max, $min) : NULL,
    );
  }

  /**
   * Parse a filesize definition from file settings into a byte integer.
   *
   * @param string $filesize
   *   The human-readable filesize (e.g. '2 MB').
   *
   * @return non-negative-int
   *   The filesize in bytes.
   */
  protected function parseFileSize(string $filesize) : int {
    // Non-64-bit PHP only supports integers up to 2 GB.
    if (PHP_INT_SIZE !== 8 /* bytes */) {
      throw new \RuntimeException('This module requires 64-bit PHP.');
    }

    $bytes = (int) Bytes::toNumber($filesize);
    assert($bytes >= 0, "File size may not be negative");
    return $bytes;
  }

  /**
   * Parse a file field settings extension string into a file extension array.
   *
   * @param string $extensions
   *   The extensions settings string for a FileItem.
   *
   * @return array
   *   The array of extensions.
   */
  protected function parseFileExtensions(string $extensions) : array {
    $trimmed = preg_replace('/ +/', ' ', trim($extensions));
    if ($trimmed === NULL) {
      return [];
    }
    return explode(' ', $trimmed);
  }

  /**
   * Parse the dimensions settings string.
   *
   * @param string $dimensions
   *   The dimensions string (e.g. 1920x1080).
   *
   * @return \Drupal\signed_file_upload\DataObject\ImageDimension|null
   *   The resolved image dimensions or NULL in case they're invalid.
   *
   * @throws \LogicException
   *   In case the configuration of the field is invalid.
   */
  protected function parseFileDimensions(string $dimensions) : ?ImageDimension {
    if ($dimensions === '') {
      return NULL;
    }

    [$width, $height] = explode('x', $dimensions) + ['', ''];

    if ($width === '' || $height === '') {
      throw new \LogicException("If image fields have a dimension specified then it should be in the correct format. Incorrectly formatted dimensions indicate an invalid configuration value that bypassed validation.");
    }

    $width = (int) $width;
    $height = (int) $height;
    assert($width > 0 && $height > 0, "Image dimensions must be positive non-zero.");
    return new ImageDimension($width, $height);
  }

  /**
   * Resolve the constraints for upload to a CKEditor.
   *
   * @param \Drupal\signed_file_upload\DataObject\EditorUploadDestination $destination
   *   The editor to upload to.
   *
   * phpcs:ignore
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   The resolved upload constraints.
   */
  protected function resolveEditorUploadDestination(EditorUploadDestination $destination) : ResolvedUploadConstraints {
    throw InvalidDestinationException::fromEditorDestination($destination, "not implemented");
  }

}
