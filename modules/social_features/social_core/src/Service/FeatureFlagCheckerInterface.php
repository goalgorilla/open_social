<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

/**
 * Runtime API for checking feature flag status.
 */
interface FeatureFlagCheckerInterface {

  /**
   * Checks whether a feature flag is enabled.
   *
   * @param string $machine_name
   *   The feature flag machine name.
   *
   * @return bool
   *   TRUE when the flag is defined and enabled.
   */
  public function isEnabled(string $machine_name): bool;

  /**
   * Returns cache tags for a feature flag.
   *
   * @param string $machine_name
   *   The feature flag machine name.
   *
   * @return string[]
   *   Cache tags to use for render cache invalidation.
   */
  public function getCacheTags(string $machine_name): array;

}
