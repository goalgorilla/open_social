<?php

namespace Drupal\social\Behat\Mailpit;

/**
 * Represents unsubscribe instructions as understandable by an inbox provider.
 */
readonly class ListUnsubscribe {

  use AssertDataStructureTrait;

  public function __construct(
    public string $header,
    public array $links,
    public string $errors,
    public string $headerPost,
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
    self::assertHasFields(
      $data,
      [
        "Header",
        "Links",
        "Errors",
        "HeaderPost",
      ],
      "Expected ListUnsubscribe structure from message.",
    );

    return new self(
      $data['Header'],
      $data['Links'],
      $data['Errors'],
      $data['HeaderPost'],
    );
  }

}
