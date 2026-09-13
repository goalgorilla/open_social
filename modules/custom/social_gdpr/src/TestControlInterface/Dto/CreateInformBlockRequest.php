<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Dto;

/**
 * Single inform block row for TCI (matches Behat table columns).
 */
final readonly class CreateInformBlockRequest {

  public function __construct(
    public string $label,
    public string $page,
    public ?int $id = NULL,
    public ?string $summary = NULL,
    public ?string $body = NULL,
    public bool $status = TRUE,
  ) {}

  /**
   * Convert the request to the array shape InformBlock::create() expects.
   *
   * @return array<string, mixed>
   *   The array with property names as keys.
   */
  public function toValuesArray(): array {
    $values = [
      'label' => $this->label,
      'page' => $this->page,
      'status' => $this->status,
    ];
    if ($this->id !== NULL) {
      $values['id'] = $this->id;
    }
    if ($this->summary !== NULL) {
      $values['summary'] = [
        'value' => $this->summary,
        'format' => 'basic_html',
      ];
    }
    if ($this->body !== NULL) {
      $values['body'] = [
        'value' => $this->body,
        'format' => 'basic_html',
      ];
    }

    return $values;
  }

}
