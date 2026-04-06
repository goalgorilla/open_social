<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Exception;

/**
 * Thrown when the user tries to perform a conflicting operation.
 *
 * Only a single operation can be performed on a file at a time. This exception
 * indicates that a client is trying to perform multiple operations at the same
 * time on the same file.
 */
class OperationConflictException extends \Exception {

}
