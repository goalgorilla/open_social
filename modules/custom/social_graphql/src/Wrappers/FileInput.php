<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers;

/**
 * Represents GraphQL file input (e.g. staged upload token) for mutations.
 */
class FileInput {

  /**
   * Constructs a FileInput.
   *
   * @param non-empty-string|null $stagedUpload
   *   Finalization token from staged upload, or NULL when not a staged upload.
   */
  public function __construct(
    public ?string $stagedUpload,
  ) {}

  /**
   * Builds an instance from raw GraphQL input array.
   *
   * @param array $input
   *   GraphQL input value for a FileInput field.
   *
   * @return static
   *   The file input wrapper.
   */
  public static function fromGraphQlInput(array $input): static {
    assert(!isset($input['stagedUpload']) || (is_string($input['stagedUpload']) && $input['stagedUpload'] !== ''), "If stagedUpload is provided as input then its GraphQL type must require it to be a non-empty string.");
    return new static(
      stagedUpload: $input['stagedUpload'],
    );
  }

  /**
   * Whether the file input is a staged upload.
   *
   * @return bool
   *   Whether the file input is a staged upload.
   *
   * @phpstan-assert-if-true non-empty-string $this->stagedUpload
   */
  public function isStagedUpload(): bool {
    return $this->stagedUpload !== NULL;
  }

}
