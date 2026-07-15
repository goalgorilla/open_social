<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

/**
 * Management API for feature flag definitions and runtime toggles.
 */
interface FeatureFlagManagerInterface {

  /**
   * Returns all discovered feature flag definitions.
   *
   * @return array<string, \Drupal\social_core\FeatureFlag\FeatureFlagDefinition>
   *   Definitions keyed by machine name.
   */
  public function getDefinitions(): array;

  /**
   * Returns validation errors for discovered feature flags.
   *
   * @return array<int, array{machine_name: string, module: string, message: string}>
   *   Structured validation errors.
   */
  public function getValidationErrors(): array;

  /**
   * Sets whether a feature flag is enabled.
   *
   * @param string $machine_name
   *   The feature flag machine name.
   * @param bool $enabled
   *   Whether the flag should be enabled.
   */
  public function setEnabled(string $machine_name, bool $enabled): void;

}
