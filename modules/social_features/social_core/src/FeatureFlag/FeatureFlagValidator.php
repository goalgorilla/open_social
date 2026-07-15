<?php

declare(strict_types=1);

namespace Drupal\social_core\FeatureFlag;

/**
 * Validates feature flag definitions discovered from YAML files.
 */
final class FeatureFlagValidator {

  /**
   * Validates discovered feature flag entries.
   *
   * @param list<\Drupal\social_core\FeatureFlag\FeatureFlagDefinitionEntry> $entries
   *   Raw definition entries.
   *
   * @return array<int, array{machine_name: string, module: string, message: string}>
   *   Structured validation errors.
   */
  public function validate(array $entries): array {
    $errors = [];
    $seen = [];

    foreach ($entries as $entry) {
      $machine_name = $entry->machineName;
      $module = $entry->provider;

      if (!preg_match('/^[a-z][a-z0-9_]*$/', $machine_name)) {
        $errors[] = [
          'machine_name' => $machine_name,
          'module' => $module,
          'message' => sprintf('Invalid machine name "%s". Use lowercase letters, numbers, and underscores.', $machine_name),
        ];
        continue;
      }

      if (isset($seen[$machine_name])) {
        $errors[] = [
          'machine_name' => $machine_name,
          'module' => $module,
          'message' => sprintf('Duplicate feature flag "%s" also defined in module "%s".', $machine_name, $seen[$machine_name]),
        ];
        continue;
      }
      $seen[$machine_name] = $module;

      try {
        FeatureFlagDefinition::fromRaw($machine_name, $module, $entry->raw);
      }
      catch (\InvalidArgumentException $exception) {
        $errors[] = [
          'machine_name' => $machine_name,
          'module' => $module,
          'message' => $exception->getMessage(),
        ];
      }
    }

    return $errors;
  }

}
