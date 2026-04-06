<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * Represents the user defined metadata for the upload.
 *
 * This corresponds to the Tus-Metadata header in the specification. The
 * contents of this object is application specific and in our case we only
 * require a filename to be specified with all other metadata being able to
 * derive from the destination.
 */
readonly class UploadMetadata {

  /**
   * Create a new upload metadata instance.
   *
   * @param non-empty-string $filename
   *   The filename to use after upload is complete.
   */
  public function __construct(
    public string $filename,
  ) {}

  /**
   * Get the file extension for the filename.
   *
   * @return non-empty-lowercase-string|null
   *   The resolved extension or NULL if the filename has no extension.
   */
  public function getFileExtension() : ?string {
    $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
    return $ext !== '' ? strtolower($ext) : NULL;
  }

  /**
   * Convert the upload metadata into a valid Upload-Metadata header.
   *
   * @return string
   *   The header value.
   */
  public function toHeader() : string {
    return "filename " . base64_encode($this->filename);
  }

  /**
   * Instantiate a metadata object from an Upload-Metadata header.
   *
   * @param string $value
   *   The Upload-Metadata header value.
   *
   * @return static
   *   An instantiated upload metadata object.
   *
   * @throws \InvalidArgumentException
   *   In case the header is invalidly encoded, has missing keys, extra keys, or
   *   the filename value is invalid.
   */
  public static function fromHeader(string $value) : static {
    $processed = [];
    $pairs = explode(',', $value);
    foreach ($pairs as $pair) {
      [$metadata_key, $metadata_value] = explode(' ', $pair) + [NULL, NULL];
      $decoded = $metadata_value !== NULL ? base64_decode($metadata_value, TRUE) : NULL;
      if ($decoded === FALSE) {
        throw new \InvalidArgumentException("Invalid base64 encoding for '$metadata_key'.");
      }
      // Tus describes that Upload-Metadata values may provide only a name
      // which then acts as a flag (e.g. `is_confidential`).
      $processed[$metadata_key] = $decoded ?? TRUE;
    }

    $required_keys = ['filename'];
    $missing_keys = array_diff($required_keys, array_keys($processed));
    $extra_keys = array_diff(array_keys($processed), $required_keys);

    if ($missing_keys !== []) {
      throw new \InvalidArgumentException("Missing metadata keys: " . implode(', ', $missing_keys));
    }
    if ($extra_keys !== []) {
      throw new \InvalidArgumentException("Unsupported metadata keys: " . implode(', ', $extra_keys));
    }

    if (!is_string($processed['filename']) || $processed['filename'] === '') {
      throw new \InvalidArgumentException("Invalid value for 'filename', non-empty string expected.");
    }

    return new static(
      filename: $processed['filename'],
    );
  }

}
