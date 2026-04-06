<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Exception;

use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;

/**
 * Thrown when the destination that was supplied is invalid.
 *
 * This indicates a bug in our code because we should be in control of what
 * destinations we upload to and not allow this to be controlled by user input.
 */
class InvalidDestinationException extends \LogicException {

  /**
   * Create the exception from an entity upload destination.
   *
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination $destination
   *   The destination.
   * @param string $reason
   *   The reason (e.g. unsupported field type).
   * @param \Throwable|null $previous
   *   A previous exception if it occurred.
   *
   * @return static
   *   An exception instance.
   */
  public static function fromEntityFieldDestination(EntityFieldUploadDestination $destination, string $reason, ?\Throwable $previous = NULL) : static {
    return new static(sprintf(
      "Invalid entity field upload destination '%s' on '%s.%s': %s.",
      $destination->fieldName,
      $destination->entityTypeId,
      $destination->bundle,
      $reason
    ), 0, $previous);
  }

  /**
   * Create the exception from an editor upload destination.
   *
   * @param \Drupal\signed_file_upload\DataObject\EditorUploadDestination $destination
   *   The destination.
   * @param string $reason
   *   The reason (e.g. invalid text format).
   * @param \Throwable|null $previous
   *   A previous exception if it occurred.
   *
   * @return static
   *   An exception instance.
   */
  public static function fromEditorDestination(EditorUploadDestination $destination, string $reason, ?\Throwable $previous = NULL) : static {
    return new static(sprintf(
      "Invalid editor upload destination for text_format '%s' in editor '%s': %s.",
      $destination->textFormatId,
      $destination->editorId,
      $reason
    ), 0, $previous);
  }

}
