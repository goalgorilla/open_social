<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\ValueObject;

use Drupal\group\Entity\GroupInterface;

/**
 * Value object for the result of validating and resolving group input.
 *
 * Encapsulates errors and resolved group entities from group input validation.
 */
final class GroupInputValidationResult {

  /**
   * Constructs a GroupInputValidationResult.
   *
   * @param string[] $errors
   *   Machine-readable error codes. Empty when validation succeeded.
   * @param \Drupal\group\Entity\GroupInterface|null $primaryGroup
   *   The resolved primary group, or NULL when invalid or not provided.
   * @param \Drupal\group\Entity\GroupInterface[] $crosspostedGroups
   *   The resolved cross-posted groups. Empty when invalid or not provided.
   */
  public function __construct(
    private readonly array $errors,
    private readonly ?GroupInterface $primaryGroup,
    private readonly array $crosspostedGroups,
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
   * Gets the resolved primary group.
   *
   * Meaningful when isValid() is TRUE. May be NULL when invalid.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The primary group or NULL.
   */
  public function getPrimaryGroup(): ?GroupInterface {
    return $this->primaryGroup;
  }

  /**
   * Gets the resolved cross-posted groups.
   *
   * Meaningful when isValid() is TRUE.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   The cross-posted groups (possibly empty).
   */
  public function getCrosspostedGroups(): array {
    return $this->crosspostedGroups;
  }

}
