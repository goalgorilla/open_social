<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Dto;

/**
 * Single taxonomy term row for TCI term creation.
 */
final readonly class CreateTermRequest {

  public function __construct(
    public string $name,
    public ?string $parent = NULL,
    public ?string $description = NULL,
    public ?int $weight = NULL,
    public ?int $tid = NULL,
  ) {}

}
