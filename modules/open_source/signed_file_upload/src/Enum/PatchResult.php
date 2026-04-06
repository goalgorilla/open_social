<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Enum;

/**
 * The result of a patch operation.
 *
 * @see \Drupal\signed_file_upload\SignedUploadFileLifecycle::patchUpload()
 */
enum PatchResult {

  // Patched successfully.
  case Ok;

  // The client-provided upload offset does not match the session offset.
  case UploadOffsetMismatch;

  // The client tried to upload more bytes than they indicated.
  case LengthExceeded;

  // The client tried to specify Upload-Length in a session where this was
  // previously done.
  case UploadLengthAlreadySet;

  // The client tried to specify a negative Upload-Length after defer-length.
  case NegativeUploadLength;
  // The client tried to specify an Upload-Length exceeding destination limits.
  case TooLargeUploadLength;
  // The offset is already past the upload length.
  case PassedUploadLength;

  // Leading bytes do not match the format implied by the declared filename.
  case InvalidContentSignature;
}
