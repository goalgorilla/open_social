<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;

/**
 * Maps an upload destination to normalized size, type, and validation rules.
 *
 * Implementations read Drupal configuration (field settings, editor image
 * upload settings, etc.) and produce a ResolvedUploadConstraints snapshot for
 * session storage and enforcement at upload and finalization time.
 */
interface UploadConstraintResolverInterface {

  /**
   * Resolves constraints for the given destination.
   *
   * For EntityFieldUploadDestination, implementations typically load
   * FieldConfig for ENTITY_TYPE.BUNDLE.FIELD_NAME and derive limits from the
   * field type (file, image, …).
   *
   * For EditorUploadDestination, implementations load the text format and
   * editor configuration and derive limits from editor image upload settings.
   *
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination $destination
   *   Where the uploaded file is intended to be used.
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   Normalized caps and the validation kind to apply when checking uploads.
   *
   * @throws \InvalidArgumentException
   *   When the destination references missing or inactive configuration, an
   *   unsupported field type, or settings that cannot be interpreted.
   */
  public function resolve(
    EntityFieldUploadDestination|EditorUploadDestination $destination,
  ): ResolvedUploadConstraints;

}
