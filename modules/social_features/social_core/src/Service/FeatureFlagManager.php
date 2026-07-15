<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\social_core\FeatureFlag\FeatureFlagDiscovery;
use Drupal\social_core\FeatureFlag\FeatureFlagState;
use Drupal\social_core\FeatureFlag\FeatureFlagValidator;

/**
 * Manages feature flag definitions, validation, and runtime toggles.
 */
final class FeatureFlagManager implements FeatureFlagManagerInterface {

  /**
   * Constructs a FeatureFlagManager object.
   */
  public function __construct(
    private readonly FeatureFlagDiscovery $discovery,
    private readonly FeatureFlagValidator $validator,
    private readonly FeatureFlagState $featureFlagState,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(): array {
    return $this->discovery->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationErrors(): array {
    return $this->validator->validate($this->discovery->getDefinitionEntries());
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled(string $machine_name, bool $enabled): void {
    $definitions = $this->getDefinitions();
    if (!isset($definitions[$machine_name])) {
      return;
    }

    if (!$this->featureFlagState->setEnabled($machine_name, $enabled)) {
      return;
    }

    $this->cacheTagsInvalidator->invalidateTags(['feature_flag:' . $machine_name]);
  }

}
