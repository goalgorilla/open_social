<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Exception;

use OpenSocial\RichTextJson\Validation\ValidationError;

/**
 * Exception thrown when document validation fails.
 *
 * This exception contains all validation errors found during validation,
 * allowing callers to inspect and report multiple issues at once.
 */
class ValidationException extends \RuntimeException {

  /**
   * Creates a new ValidationException.
   *
   * @param array<int, \OpenSocial\RichTextJson\Validation\ValidationError> $errors
   *   The validation errors.
   * @param int $code
   *   The error code.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    private readonly array $errors,
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    $message = self::buildMessage($errors);
    parent::__construct($message, $code, $previous);
  }

  /**
   * Gets the validation errors.
   *
   * @return array<int, \OpenSocial\RichTextJson\Validation\ValidationError>
   *   The validation errors.
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Builds the exception message from validation errors.
   *
   * @param array<int, \OpenSocial\RichTextJson\Validation\ValidationError> $errors
   *   The validation errors.
   *
   * @return string
   *   The formatted message.
   */
  private static function buildMessage(array $errors): string {
    $count = count($errors);
    if ($count === 0) {
      return 'Document validation failed';
    }

    if ($count === 1) {
      $error = $errors[0];
      return sprintf(
        'Document validation failed: %s at %s',
        $error->getMessage(),
        $error->getPath(),
      );
    }

    $messages = array_map(
      static fn(ValidationError $e): string => sprintf('%s at %s', $e->getMessage(), $e->getPath()),
      $errors,
    );

    return sprintf(
      'Document validation failed with %d errors: %s',
      $count,
      implode('; ', $messages),
    );
  }

}
