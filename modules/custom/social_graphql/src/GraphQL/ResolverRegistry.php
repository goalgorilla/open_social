<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverRegistry as ResolverRegistryBase;

/**
 * The Open Social resolver registry.
 *
 * Extends the base ResolverRegistry to provide a way to implement common
 * helpers and to register type config overrides (e.g. for custom scalars).
 *
 * Previously included resolver inheritance which has been moved into the
 * GraphQL module.
 */
class ResolverRegistry extends ResolverRegistryBase {

  /**
   * Type config overrides keyed by type name (e.g. scalar parseValue/serialize).
   *
   * @var array<string, array<string, mixed>>
   */
  protected array $typeConfigOverrides = [];

  /**
   * Adds a type config override for a named type (e.g. custom scalar).
   *
   * Used when building the schema so that types like RichTextJSON get
   * parseValue, parseLiteral, and serialize behavior from the registry
   * instead of from the GraphQL library's default.
   *
   * @param string $typeName
   *   The type name (e.g. 'RichTextJSON').
   * @param array<string, mixed> $config
   *   The type config to merge (e.g. 'parseValue', 'parseLiteral', 'serialize').
   *
   * @return $this
   */
  public function addTypeConfigOverride(string $typeName, array $config): self {
    $this->typeConfigOverrides[$typeName] = $config;
    return $this;
  }

  /**
   * Gets the type config override for a type name.
   *
   * @param string $typeName
   *   The type name.
   *
   * @return array<string, mixed>|null
   *   The override config, or NULL if none.
   */
  public function getTypeConfigOverride(string $typeName): ?array {
    return $this->typeConfigOverrides[$typeName] ?? NULL;
  }

}
