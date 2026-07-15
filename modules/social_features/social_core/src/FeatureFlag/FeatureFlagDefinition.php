<?php

declare(strict_types=1);

namespace Drupal\social_core\FeatureFlag;

/**
 * Value object for a discovered feature flag definition.
 */
final readonly class FeatureFlagDefinition {

  /**
   * Constructs a FeatureFlagDefinition object.
   */
  public function __construct(
    public string $id,
    public string $label,
    public string $description,
    public string $dateIntroduced,
    public string $provider,
  ) {}

  /**
   * Creates a definition from raw YAML data.
   *
   * @param string $id
   *   The feature flag machine name.
   * @param string $provider
   *   The providing module name.
   * @param mixed $raw
   *   The raw YAML definition.
   *
   * @throws \InvalidArgumentException
   *   When the raw definition is malformed.
   */
  public static function fromRaw(string $id, string $provider, mixed $raw): self {
    if (!is_array($raw)) {
      throw new \InvalidArgumentException(sprintf('Feature flag "%s" must be defined as a mapping.', $id));
    }

    $label = $raw['label'] ?? NULL;
    $description = $raw['description'] ?? NULL;
    $date_introduced = $raw['date_introduced'] ?? NULL;

    if (!is_string($label) || trim($label) === '') {
      throw new \InvalidArgumentException(sprintf('Feature flag "%s" is missing a non-empty label.', $id));
    }
    if (!is_string($description) || trim($description) === '') {
      throw new \InvalidArgumentException(sprintf('Feature flag "%s" is missing a non-empty description.', $id));
    }
    if (!is_string($date_introduced) || trim($date_introduced) === '') {
      throw new \InvalidArgumentException(sprintf('Feature flag "%s" is missing a non-empty date_introduced.', $id));
    }

    return new self(
      $id,
      trim($label),
      trim($description),
      trim($date_introduced),
      $provider,
    );
  }

}
