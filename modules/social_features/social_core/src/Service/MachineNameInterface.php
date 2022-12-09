<?php

namespace Drupal\social_core\Service;

/**
 * Defines the machine name service interface.
 */
interface MachineNameInterface {

  /**
   * Transforms given string to machine name.
   *
   * @param string $value
   *   The value to be transformed.
   * @param string $pattern
   *   The replacement pattern for regex.
   */
  public function transform(
    string $value,
    string $pattern = '/[^a-z0-9_]+/'
  ): string;

}
