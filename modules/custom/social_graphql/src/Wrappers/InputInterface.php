<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers;

/**
 * Provides an interface for input types.
 */
interface InputInterface {

  /**
   * Set the values for this input.
   *
   * @param array $input
   *   The input array as it comes from the GraphQL schema.
   */
  public function setValues(array $input) : void;

  /**
   * Validates the input.
   *
   * Must be called after setValues but before any data is accessed.
   *
   * @return bool
   *   Whether the validation was successful.
   */
  public function validate() : bool;

  /**
   * Whether this input has any violations.
   *
   * @return bool
   *   Whether this input has any violations.
   */
  public function hasViolations() : bool;

  /**
   * Get the violations on this input.
   *
   * @return \Drupal\social_graphql\GraphQL\ViolationInterface[]
   *   An array of violations that were detected during validation or an empty
   *   array if there are no violations.
   */
  public function getViolations() : array;

}
