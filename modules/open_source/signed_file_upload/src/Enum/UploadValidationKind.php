<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Enum;

/**
 * The different types of validation available.
 */
enum UploadValidationKind: string {

  case FileField = "file";
  case ImageField = "image";
  case EditorInlineImage = "editor_image";

}
