<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Enum;

/**
 * The state in which an upload session exists.
 */
enum UploadState: string {

  // The initial state of an upload session.
  case Uploading = "uploading";

  // The session has expired and the artifact has been cleaned up.
  case Expired = "expired";

  // The upload session was terminated by the client, and the artifact has been
  // cleaned up.
  case Terminated = "terminated";

  // The session has been successfully finalized.
  case Finalized = "finalized";

}
