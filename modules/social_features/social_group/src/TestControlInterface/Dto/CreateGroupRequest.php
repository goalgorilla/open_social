<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Dto;

/**
 * Single group row for the group creation operations.
 *
 * This is not every field a group can have. It is the union of the fields
 * the groups under test are created with, across the several bundles they
 * use (flexible_group, course_basic, …), so extend it when a test needs a
 * field rather than trying to cover the full field list speculatively.
 */
final readonly class CreateGroupRequest {

  public function __construct(
    public string $label,
    public string $type,
    public ?string $author = NULL,
    public ?string $langcode = NULL,
    public ?string $created = NULL,
    public ?string $field_group_description = NULL,
    public ?string $field_flexible_group_visibility = NULL,
    public ?string $field_group_allowed_visibility = NULL,
    public ?string $field_group_allowed_join_method = NULL,
    public ?string $field_course_opening_status = NULL,
    public ?string $field_course_order = NULL,
    public ?string $field_group_discussion = NULL,
    public ?string $field_exclude_from_library = NULL,
    public ?string $role_visibility = NULL,
    public ?string $enable_files = NULL,
    public ?string $enable_books = NULL,
    public ?string $path = NULL,
    public ?string $id = NULL,
    public ?string $status = NULL,
  ) {}

  /**
   * Create a new copy of this request with an author.
   *
   * @param string $authorName
   *   The name of the author.
   *
   * @return self
   *   The copy of the request object.
   */
  public function withAuthor(string $authorName): self {
    // We must manually clone until PHP 8.5 allows cloning with changes.
    return new self(
      label: $this->label,
      type: $this->type,
      author: $authorName,
      langcode: $this->langcode,
      created: $this->created,
      field_group_description: $this->field_group_description,
      field_flexible_group_visibility: $this->field_flexible_group_visibility,
      field_group_allowed_visibility: $this->field_group_allowed_visibility,
      field_group_allowed_join_method: $this->field_group_allowed_join_method,
      field_course_opening_status: $this->field_course_opening_status,
      field_course_order: $this->field_course_order,
      field_group_discussion: $this->field_group_discussion,
      field_exclude_from_library: $this->field_exclude_from_library,
      role_visibility: $this->role_visibility,
      enable_files: $this->enable_files,
      enable_books: $this->enable_books,
      path: $this->path,
      id: $this->id,
      status: $this->status,
    );
  }

  /**
   * Convert the object to an array.
   *
   * @return array<string, mixed>
   *   The array with property names as keys.
   */
  public function toRowArray(): array {
    $out = [];
    foreach ((new \ReflectionClass($this))->getProperties() as $prop) {
      $val = $prop->getValue($this);
      if ($val === NULL || $val === '') {
        continue;
      }
      $out[$prop->getName()] = $val;
    }
    return $out;
  }

}
