<?php

declare(strict_types=1);

namespace Drupal\entity_access_by_field\Traits;

/**
 * Trait for handling visibility validation and conversion.
 */
trait VisibilityTrait {

  /**
   * Maps visibility to Drupal field values.
   *
   * @return array
   *   Map for visibility values.
   */
  protected static function getVisibilityMap(): array {
    return [
      'PUBLIC' => 'public',
      'COMMUNITY' => 'community',
      'GROUP_MEMBER' => 'group',
    ];
  }

  /**
   * Gets all valid visibility.
   *
   * @return array
   *   An array of valid visibility values.
   */
  protected static function getValidVisibilityValues(): array {
    return array_keys(self::getVisibilityMap());
  }

  /**
   * Checks if a visibility value is valid.
   *
   * @param string $visibility
   *   The visibility to validate.
   *
   * @return bool
   *   TRUE if the visibility value is valid, FALSE otherwise.
   */
  protected function isValidVisibility(string $visibility): bool {
    return in_array($visibility, self::getValidVisibilityValues(), TRUE);
  }

  /**
   * Converts a visibility to a field value.
   *
   * @param string $graphql_visibility
   *   The visibility.
   * @param string|null $default
   *   Default value to return if conversion fails.
   *
   * @return string
   *   The field value.
   */
  protected function convertVisibilityUserInputToConstant(string $graphql_visibility, ?string $default = 'community'): string {
    $visibility_map = self::getVisibilityMap();
    return $visibility_map[$graphql_visibility] ?? $default;
  }

}
