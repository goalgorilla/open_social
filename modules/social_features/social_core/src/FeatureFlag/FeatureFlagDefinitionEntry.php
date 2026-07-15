<?php

declare(strict_types=1);

namespace Drupal\social_core\FeatureFlag;

/**
 * Raw feature flag definition entry discovered from YAML.
 */
final readonly class FeatureFlagDefinitionEntry {

  /**
   * Constructs a FeatureFlagDefinitionEntry object.
   */
  public function __construct(
    public string $machineName,
    public string $provider,
    public mixed $raw,
  ) {}

}
