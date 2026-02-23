<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Validation;

/**
 * Represents the result of validating a document.
 */
final class ValidationResult {

  /**
   * Creates a new ValidationResult.
   *
   * @param array<int, ValidationError> $errors
   *   The validation errors.
   */
  public function __construct(
    private readonly array $errors = [],
  ) {}

  /**
   * Checks if the validation passed.
   *
   * @return bool
   *   TRUE if there are no errors.
   */
  public function isValid(): bool {
    return $this->errors === [];
  }

  /**
   * Gets the validation errors.
   *
   * @return array<int, ValidationError>
   *   The errors.
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
