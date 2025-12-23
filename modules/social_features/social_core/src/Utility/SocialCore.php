<?php

declare(strict_types=1);

namespace Drupal\social_core\Utility;

/**
 * Provides core functionalities for managing social plugins or integrations.
 *
 * The SocialCore class is designed to handle compatibility adjustments,
 * feature enhancements, and utility methods for specific social-related
 * integrations or third-party plugins.
 */
class SocialCore {

  /**
   * Ensures compatibility between Better Exposed Filters and Select2 elements.
   *
   * This method removes process callbacks and pre_render callbacks from select2
   * form elements when they are used with the Better Exposed Filters (BEF)
   * plugin. This is necessary because BEF's processing can interfere with
   * select2's initialization and functionality.
   *
   * @param array &$element
   *   A form element array, passed by reference.
   */
  public static function convertSelectToSelect2(array &$element): void {
    // Apply only for select2 elements.
    if ($element['#type'] !== 'select') {
      return;
    }

    $element['#type'] = 'select2';

    if (!isset($element['#context']['#plugin_type'])) {
      return;
    }

    if ($element['#context']['#plugin_type'] !== 'bef') {
      return;
    }

    // We need to unset all process callbacks to make "select2" work
    // properly if the "Better Exposed Filters" plugin is used.
    unset($element['#process']);
    unset($element['#pre_render']);
  }

  /**
   * Inserts an element after a specific key in an associative array.
   *
   * This method takes an associative array by reference and inserts a new
   * key-value pair immediately after the specified key. If the target key is
   * not found, the new element will be appended to the end of the array.
   * If the new value is not provided, it will use the existing value from
   * the array at the new key position.
   *
   * @param array &$array
   *   The associative array to modify, passed by reference.
   * @param string|int $after_key
   *   The key after which the new element should be inserted.
   * @param string|int $target_key
   *   The key for the new element to insert.
   * @param mixed $new_value
   *   The value for the new element to insert. If not provided, uses the
   *   existing value from $array[$new_key].
   */
  public static function insertAfterKey(array &$array, string|int $after_key, string|int $target_key, mixed $new_value = NULL): void {
    // Determine the value to use for the new element.
    if ($new_value === NULL && array_key_exists($target_key, $array)) {
      $new_value = $array[$target_key];
    }

    // If the array is empty, simply add the new element.
    if (empty($array)) {
      $array[$target_key] = $new_value;
      return;
    }

    // If the target key doesn't exist, append the new element to the end.
    if (!array_key_exists($after_key, $array)) {
      $array[$target_key] = $new_value;
      return;
    }

    $result = [];

    foreach ($array as $key => $value) {
      // Skip the new key if it already exists to avoid duplication.
      if ($key === $target_key) {
        continue;
      }

      $result[$key] = $value;

      // Insert the new element immediately after the target key.
      if ($key === $after_key) {
        $result[$target_key] = $new_value;
      }
    }

    // Replace the original array with the modified result.
    $array = $result;
  }

}
