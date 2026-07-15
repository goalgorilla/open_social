<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

use Drupal\social_core\FeatureFlag\FeatureFlagDiscovery;
use Drupal\social_core\FeatureFlag\FeatureFlagState;

/**
 * Checks whether defined feature flags are enabled.
 */
final class FeatureFlagChecker implements FeatureFlagCheckerInterface {

  /**
   * Constructs a FeatureFlagChecker object.
   */
  public function __construct(
    private readonly FeatureFlagDiscovery $discovery,
    private readonly FeatureFlagState $featureFlagState,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isEnabled(string $machine_name): bool {
    $definitions = $this->discovery->getDefinitions();
    if (!isset($definitions[$machine_name])) {
      return FALSE;
    }

    $enabled = $this->featureFlagState->getEnabled();
    return !empty($enabled[$machine_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(string $machine_name): array {
    return ['feature_flag:' . $machine_name];
  }

}
