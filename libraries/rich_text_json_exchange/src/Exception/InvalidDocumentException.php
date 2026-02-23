<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Exception;

/**
 * Exception thrown when document structure is invalid.
 */
class InvalidDocumentException extends \RuntimeException {

  /**
   * The JSON pointer path where the error occurred.
   *
   * @var string
   */
  private string $path;

  /**
   * Creates a new InvalidDocumentException.
   *
   * @param string $message
   *   The error message.
   * @param string $path
   *   The JSON pointer path where the error occurred.
   * @param int $code
   *   The error code.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    string $message,
    string $path = '',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    $this->path = $path;
    $fullMessage = $path !== '' ? sprintf('%s at %s', $message, $path) : $message;
    parent::__construct($fullMessage, $code, $previous);
  }

  /**
   * Gets the JSON pointer path where the error occurred.
   *
   * @return string
   *   The path.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Creates an exception for a missing required field.
   *
   * @param string $field
   *   The field name.
   * @param string $path
   *   The JSON pointer path.
   *
   * @return self
   *   A new exception instance.
   */
  public static function missingField(string $field, string $path = ''): self {
    return new self(
      sprintf('Missing required field "%s"', $field),
      $path,
    );
  }

  /**
   * Creates an exception for an invalid field type.
   *
   * @param string $field
   *   The field name.
   * @param string $expectedType
   *   The expected type.
   * @param string $path
   *   The JSON pointer path.
   *
   * @return self
   *   A new exception instance.
   */
  public static function invalidFieldType(string $field, string $expectedType, string $path = ''): self {
    return new self(
      sprintf('Field "%s" must be %s', $field, $expectedType),
      $path,
    );
  }

  /**
   * Creates an exception for an invalid field value.
   *
   * @param string $field
   *   The field name.
   * @param string $reason
   *   The reason why the value is invalid.
   * @param string $path
   *   The JSON pointer path.
   *
   * @return self
   *   A new exception instance.
   */
  public static function invalidFieldValue(string $field, string $reason, string $path = ''): self {
    return new self(
      sprintf('Invalid value for field "%s": %s', $field, $reason),
      $path,
    );
  }

}
