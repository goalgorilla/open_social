<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\social_core\Service\FeatureFlagManagerInterface;

/**
 * Provides steps for enabling and disabling social feature flags in tests.
 */
class FeatureFlagContext extends RawMinkContext {

  /**
   * The feature flag manager.
   */
  private ?FeatureFlagManagerInterface $featureFlagManager = NULL;

  /**
   * Lazy load the feature flag manager.
   *
   * @return \Drupal\social_core\Service\FeatureFlagManagerInterface
   *   The feature flag manager.
   */
  public function featureFlagManager() : FeatureFlagManagerInterface {
    return $this->featureFlagManager ??= \Drupal::service(FeatureFlagManagerInterface::class);
  }

  /**
   * Enables a feature flag for the current test scenario.
   *
   * @Given the :flag feature flag is enabled
   */
  public function enableFeatureFlag(string $flag): void {
    $this->featureFlagManager()->setEnabled($flag, TRUE);
  }

  /**
   * Disables a feature flag for the current test scenario.
   *
   * @Given the :flag feature flag is disabled
   */
  public function disableFeatureFlag(string $flag): void {
    $this->featureFlagManager()->setEnabled($flag, FALSE);
  }

}
