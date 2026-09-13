<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Dto;

/**
 * Single data policy row for TCI (matches Behat table columns).
 */
final readonly class CreateDataPolicyRequest {

  public function __construct(
    public string $name,
    public string $field_description,
    public ?string $revision_log_message = NULL,
  ) {}

  /**
   * Convert the request to the array shape DataPolicy::create() expects.
   *
   * @return array<string, mixed>
   *   The array with property names as keys.
   */
  public function toValuesArray(): array {
    $values = [
      'name' => $this->name,
      'field_description' => $this->field_description,
    ];
    if ($this->revision_log_message !== NULL) {
      $values['revision_log_message'] = $this->revision_log_message;
    }

    return $values;
  }

}
