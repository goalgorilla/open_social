<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Dto;

/**
 * Single topic row for TCI (matches Behat table columns for topic creation).
 */
final readonly class CreateTopicRequest {

  public function __construct(
    public string $title,
    public string $body,
    public string $field_content_visibility,
    public string $field_topic_type,
    public ?string $author = NULL,
    public bool $status = TRUE,
    public ?string $langcode = "en",
    public ?string $path = NULL,
    public ?string $created = NULL,
    public ?string $group = NULL,
    public ?bool $field_exclude_from_library = NULL,
    public ?string $role_visibility = NULL,
    public ?string $field_segment_visibility = NULL,
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
  public function withAuthor(string $authorName) : self {
    // We must manually clone until PHP 8.5 allows cloning with changes.
    return new self(
      title: $this->title,
      body: $this->body,
      field_content_visibility: $this->field_content_visibility,
      field_topic_type: $this->field_topic_type,
      author: $authorName,
      status: $this->status,
      langcode: $this->langcode,
      path: $this->path,
      created: $this->created,
      group: $this->group,
      field_exclude_from_library: $this->field_exclude_from_library,
      role_visibility: $this->role_visibility,
      field_segment_visibility: $this->field_segment_visibility,
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
