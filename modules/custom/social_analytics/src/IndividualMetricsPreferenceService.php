<?php

declare(strict_types=1);

namespace Drupal\social_analytics;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;

/**
 * Reads and writes per-user individual metrics visibility preferences.
 */
final class IndividualMetricsPreferenceService {

  private const MODULE = 'social_analytics';

  private const KEY_SHOW_IN_INDIVIDUAL_METRICS = 'show_in_individual_metrics';

  public function __construct(
    private readonly UserDataInterface $userData,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Whether the user has an explicitly saved preference.
   */
  public function hasStoredPreference(int $uid): bool {
    return $this->userData->get(self::MODULE, $uid, self::KEY_SHOW_IN_INDIVIDUAL_METRICS) !== NULL;
  }

  /**
   * Returns the stored preference, or NULL when the user never saved.
   */
  public function getStoredShowInIndividualMetrics(int $uid): ?bool {
    $value = $this->userData->get(self::MODULE, $uid, self::KEY_SHOW_IN_INDIVIDUAL_METRICS);
    if ($value === NULL) {
      return NULL;
    }

    return (bool) $value;
  }

  /**
   * Returns the effective preference for display and Lumina resolution.
   */
  public function getEffectiveShowInIndividualMetrics(int $uid): bool {
    $stored = $this->getStoredShowInIndividualMetrics($uid);
    if ($stored !== NULL) {
      return $stored;
    }

    return (bool) $this->configFactory
      ->get('social_analytics.settings')
      ->get('individual_metrics_show_by_default');
  }

  /**
   * Persists an explicit user preference.
   */
  public function setShowInIndividualMetrics(int $uid, bool $value): void {
    $this->userData->set(self::MODULE, $uid, self::KEY_SHOW_IN_INDIVIDUAL_METRICS, $value);
  }

}
