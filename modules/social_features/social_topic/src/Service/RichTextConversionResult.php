<?php

declare(strict_types=1);

namespace Drupal\social_topic\Service;

/**
 * Result of converting Rich Text JSON to HTML.
 */
final class RichTextConversionResult {

  /**
   * Constructs a RichTextConversionResult.
   *
   * @param string|null $html
   *   The rendered HTML, or NULL if conversion failed.
   * @param \Drupal\social_graphql\GraphQL\ViolationInterface[] $violations
   *   Validation violations when conversion failed.
   */
  public function __construct(
    private readonly ?string $html,
    private readonly array $violations = [],
  ) {}

  /**
   * Whether the conversion succeeded.
   */
  public function isValid(): bool {
    return $this->html !== NULL && empty($this->violations);
  }

  /**
   * The rendered HTML.
   *
   * @return string|null
   *   The HTML string, or NULL if conversion failed.
   */
  public function getHtml(): ?string {
    return $this->html;
  }

  /**
   * Validation violations.
   *
   * @return \Drupal\social_graphql\GraphQL\ViolationInterface[]
   *   The violations.
   */
  public function getViolations(): array {
    return $this->violations;
  }

}
