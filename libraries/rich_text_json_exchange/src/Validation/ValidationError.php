<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Validation;

/**
 * Represents a validation error with a JSON pointer path.
 */
final class ValidationError {

  /**
   * Creates a new ValidationError.
   *
   * @param string $message
   *   The error message.
   * @param string $path
   *   The JSON pointer path where the error occurred.
   */
  public function __construct(
    private readonly string $message,
    private readonly string $path,
  ) {}

  /**
   * Gets the error message.
   *
   * @return string
   *   The error message.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Gets the JSON pointer path.
   *
   * @return string
   *   The path where the error occurred.
   */
  public function getPath(): string {
    return $this->path;
  }

}
