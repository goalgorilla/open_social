<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Dto;

/**
 * Single role row for the role creation operation.
 */
final readonly class CreateRoleRequest {

  /**
   * Create a new role row.
   *
   * @param string $label
   *   The role label.
   * @param string|null $id
   *   The role machine name. Defaults to the lowercased label.
   * @param string|null $langcode
   *   Accepted and ignored. Roles are created in the site's default language
   *   regardless of this value.
   */
  public function __construct(
    public string $label,
    public ?string $id = NULL,
    public ?string $langcode = NULL,
  ) {}

}
