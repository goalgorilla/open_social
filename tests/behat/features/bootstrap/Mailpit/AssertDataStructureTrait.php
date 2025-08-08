<?php

namespace Drupal\social\Behat\Mailpit;

/**
 * Helps validate that the structure from the API doesn't change.
 */
trait AssertDataStructureTrait {

  /**
   * Check that expected fields are present.
   *
   * Runs an assert() to ensure that the fields are present. The message of the
   * assert will be $message and the list of missing fields.
   *
   * @param array $data
   *   The data to check.
   * @param array $requiredFields
   *   The fields that should be in $data as keys.
   * @param string $message
   *   The message in case fields are missing.
   */
  protected static function assertHasFields(array $data, array $requiredFields, string $message) : void {
    $missing = array_diff(
      $requiredFields,
      array_keys($data)
    );
    assert(
      $missing === [],
      $message . " Missing fields: " . implode(", ", $missing)
    );
  }

}
