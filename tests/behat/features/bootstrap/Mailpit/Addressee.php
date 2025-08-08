<?php

namespace Drupal\social\Behat\Mailpit;

/**
 * Represents an addressee in a message as used in "to" or "from" fields.
 */
readonly class Addressee {

  use AssertDataStructureTrait;

  public function __construct(
    public string $name,
    public string $address,
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
      ['Name', 'Address'],
      "A recipient should have 'Name' and 'Address' fields.",
    );

    return new self(
      $data['Name'],
      $data['Address'],
    );
  }

}
