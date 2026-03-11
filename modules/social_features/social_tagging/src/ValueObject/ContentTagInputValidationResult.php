<?php

declare(strict_types=1);

namespace Drupal\social_tagging\ValueObject;

/**
 * Value object for the result of validating and resolving content tag input.
 *
 * Encapsulates errors and resolved taxonomy term entities from content tag
 * input validation.
 */
final class ContentTagInputValidationResult {

  /**
   * Constructs a ContentTagInputValidationResult.
   *
   * @param string[] $errors
   *   Machine-readable error codes. Empty when validation succeeded.
   * @param \Drupal\taxonomy\TermInterface[] $tags
   *   The resolved content tags (taxonomy terms). May contain partial results
   *   even when validation yielded errors (e.g. some tags valid, some invalid).
   */
  public function __construct(
    private readonly array $errors,
    private readonly array $tags,
  ) {}

  /**
   * Whether validation passed with no errors.
   *
   * @return bool
   *   TRUE when there are no errors; FALSE otherwise.
   */
  public function isValid(): bool {
    return empty($this->errors);
  }

  /**
   * Gets the validation error codes.
   *
   * @return string[]
   *   List of machine-readable error code strings.
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Gets the resolved content tags.
   *
   * May contain resolved tags even when validation yielded errors (partial
   * success). When isValid() is TRUE, all requested tags that passed validation
   * are included.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The resolved content tags (possibly empty).
   */
  public function getTags(): array {
    return $this->tags;
  }

}
