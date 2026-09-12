<?php

declare(strict_types=1);

namespace Drupal\social_group_request\TestControlInterface\Dto;

/**
 * Single membership-request row for the `group requests:` step.
 *
 * This is not every field a request can have. It is the union of the fields
 * the requests under test are created with, so extend it when a test needs
 * a field rather than trying to cover the full field list speculatively.
 */
final readonly class GroupRequestRow {

  public function __construct(
    public string $group,
    public string $user,
    public ?string $created = NULL,
    public ?string $grequest_status = NULL,
    public ?string $field_grequest_message = NULL,
    public ?string $field_request_purpose = NULL,
    public ?string $field_access_level = NULL,
  ) {}

  /**
   * Convert the row to group_content field values, excluding group/user.
   *
   * `group` and `user` are resolved to `gid` and `entity_id` by the caller,
   * since they need an entity lookup first.
   *
   * @return array<string, mixed>
   *   The array with property names as keys.
   */
  public function toFieldsArray(): array {
    $out = [];
    foreach ((new \ReflectionClass($this))->getProperties() as $prop) {
      if (in_array($prop->getName(), ['group', 'user'], TRUE)) {
        continue;
      }
      $val = $prop->getValue($this);
      if ($val === NULL || $val === '') {
        continue;
      }
      $out[$prop->getName()] = $val;
    }
    return $out;
  }

}
