<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers\Input;

use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\SignedFileUpload\StagedUploadTargetEnumDestinationResolverTrait;
use Drupal\social_graphql\Wrappers\InputBase;
use GraphQL\Type\Schema;

/**
 * Input wrapper for the stagedUploadsCreate GraphQL mutation.
 *
 * Parses each request row: filename metadata, optional size, and resolves
 * `target` to a concrete upload destination using schema enum directives.
 *
 * @phpstan-type Requests array<array{ target: string, metadata: UploadMetadata, fileSize: ?non-negative-int, destination: \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination }>
 */
class StagedUploadsCreateInput extends InputBase {

  use StagedUploadTargetEnumDestinationResolverTrait;

  /**
   * Normalized requests after setValues(); empty until setValues() runs.
   *
   * @var Requests
   */
  protected array $requests;

  /**
   * Constructs the mutation input wrapper.
   *
   * @param \GraphQL\Type\Schema $schema
   *   Active GraphQL schema; used to map StagedUploadTarget values to
   *   destinations via enum directives.
   */
  public function __construct(
    protected Schema $schema,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Populates $this->requests from the `requests` list and records violations
   * for empty targets, negative sizes, or invalid extensions when applicable.
   */
  public function setValues(array $input): void {
    parent::setValues($input);

    if ($input['requests'] === []) {
      $this->violations[] = new Violation("MISSING_REQUESTS");
    }

    $this->requests = array_filter(array_map(
      function ($request) {
        $metadata = new UploadMetadata(
          filename: $request['filename'],
        );

        $fileSize = $request['fileSize'] ?? NULL;
        if ($fileSize !== NULL && $fileSize < 0) {
          $this->violations[] = new Violation("NEGATIVE_FILESIZE");
        }

        $target = $request['target'] ?? NULL;
        if (!is_string($target) || $target === '') {
          $this->violations[] = new Violation("TARGET_EMPTY");
          return NULL;
        }

        $destination = $this->getDestinationFromEnum($this->schema, $target);

        return [
          'target' => $target,
          'metadata' => $metadata,
          'fileSize' => $fileSize,
          'destination' => $destination,
        ];
      },
      $input['requests'],
    ));
  }

  /**
   * Ensures each request has a filename extension allowed for staging.
   *
   * @return bool
   *   TRUE when there are no violations after this pass.
   */
  public function validate(): bool {
    foreach ($this->requests as $request) {
      if ($request['metadata']->getFileExtension() === NULL) {
        $this->violations[] = new Violation("MISSING_FILE_EXTENSION");
      }
    }

    return !$this->hasViolations();
  }

  /**
   * Returns normalized requests for the upload manager.
   *
   * @phpstan-return Requests
   */
  public function getRequests(): array {
    return $this->requests;
  }

}
