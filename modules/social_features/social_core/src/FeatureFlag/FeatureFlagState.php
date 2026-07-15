<?php

declare(strict_types=1);

namespace Drupal\social_core\FeatureFlag;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;

/**
 * Persists feature flag enabled states with locking.
 */
final class FeatureFlagState {

  /**
   * State key storing enabled flag statuses.
   */
  public const STATE_KEY = 'social_core.feature_flags.enabled';

  /**
   * Lock name for state read-modify-write operations.
   */
  private const LOCK_NAME = 'social_core.feature_flags.state';

  /**
   * Constructs a FeatureFlagState object.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly LockBackendInterface $lock,
  ) {}

  /**
   * Returns the current enabled states keyed by machine name.
   *
   * This does not lock since it's in the hot-path. Worst case this means a
   * slightly outdated answer if feature flags are being updated at the same
   * time.
   *
   * @return array<string, bool>
   *   Enabled states.
   */
  public function getEnabled(): array {
    $enabled = $this->state->get(self::STATE_KEY, []);
    return is_array($enabled) ? $enabled : [];
  }

  /**
   * Sets whether a feature flag is enabled.
   *
   * This is a locking function to ensure feature flag write operations can not
   * have a race condition.
   *
   * @return bool
   *   TRUE when the stored value changed.
   */
  public function setEnabled(string $machine_name, bool $enabled): bool {
    return $this->withStateLock(function () use ($machine_name, $enabled): bool {
      $states = $this->state->get(self::STATE_KEY, []);
      if (!is_array($states)) {
        $states = [];
      }

      $currently_enabled = !empty($states[$machine_name]);
      if ($enabled === $currently_enabled) {
        return FALSE;
      }

      if ($enabled) {
        $states[$machine_name] = TRUE;
      }
      else {
        unset($states[$machine_name]);
      }

      $this->state->set(self::STATE_KEY, $states);
      return TRUE;
    });
  }

  /**
   * Removes state entries for flags that are no longer defined.
   *
   * This is a locking function to ensure feature flag write operations can not
   * have a race condition.
   *
   * @param string[] $defined_machine_names
   *   Machine names still defined on disk.
   */
  public function prune(array $defined_machine_names): void {
    $this->withStateLock(function () use ($defined_machine_names): void {
      $enabled = $this->state->get(self::STATE_KEY, []);
      if (!is_array($enabled) || $enabled === []) {
        return;
      }

      $defined_lookup = array_fill_keys($defined_machine_names, TRUE);
      $pruned = array_intersect_key($enabled, $defined_lookup);

      if ($pruned !== $enabled) {
        $this->state->set(self::STATE_KEY, $pruned);
      }
    });
  }

  /**
   * Executes a callback while holding the feature flag state lock.
   *
   * @param callable(): T $callback
   *   The callback to execute.
   *
   * @return T
   *   The callback return value.
   *
   * @template T
   */
  private function withStateLock(callable $callback): mixed {
    if (!$this->lock->acquire(self::LOCK_NAME)) {
      throw new \RuntimeException('Could not acquire the feature flag state lock.');
    }

    try {
      return $callback();
    }
    finally {
      $this->lock->release(self::LOCK_NAME);
    }
  }

}
