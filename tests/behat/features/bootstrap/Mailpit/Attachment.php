<?php

namespace Drupal\social\Behat\Mailpit;

/**
 * Represents an attachment in a message.
 */
readonly class Attachment {

  use AssertDataStructureTrait;

  public function __construct(
    public string $contentId,
    public string $contentType,
    public string $fileName,
    public string $partId,
    public int $size,
  ) {
  }

  /**
   * Create the data object from an array of values.
   *
   * @param array $data
   *   The array of values as provided by the API.
   *
   * @return self
   *   The data object.
   */
  public static function fromArray(array $data) : self {
    static::assertHasFields(
      $data,
      [
        "ContentID",
        "ContentType",
        "FileName",
        "PartID",
        "Size",
      ],
      "Expected Attachments structure from message."
    );

    return new self(
      $data['ContentID'],
      $data['ContentType'],
      $data['FileName'],
      $data['PartID'],
      $data['Size'],
    );
  }

}
