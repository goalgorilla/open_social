<?php

declare(strict_types=1);

namespace Drupal\social_graphql\GraphQL;

use Drupal\graphql\GraphQL\ResolverRegistry as ResolverRegistryBase;

/**
 * The Open Social resolver registry.
 *
 * Extends the base ResolverRegistry to provide a way to implement common
 * helpers and to register custom scalars.
 *
 * Previously included resolver inheritance which has been moved into the
 * GraphQL module.
 */
class ResolverRegistry extends ResolverRegistryBase {

  /**
   * Custom scalars keyed by name.
   *
   * @var array<string, class-string<\Drupal\social_graphql\Plugin\GraphQL\Types\CustomScalarInterface<mixed>>>
   */
  protected array $customScalars = [];

  /**
   * Adds a custom scalar definition.
   *
   * @param string $typeName
   *   The type name (e.g. 'RichTextJSON').
   * @param class-string<\Drupal\social_graphql\Plugin\GraphQL\Types\CustomScalarInterface<mixed>> $scalar
   *   The scalar definition class.
   *
   * @return $this
   */
  public function addCustomScalar(string $typeName, string $scalar): self {
    $this->customScalars[$typeName] = $scalar;
    return $this;
  }

  /**
   * Gets the scalar configuration by name.
   *
   * @param string $typeName
   *   The type name.
   *
   * @return class-string<\Drupal\social_graphql\Plugin\GraphQL\Types\CustomScalarInterface<mixed>>|null
   *   The custom scalar definition class, or NULL if none.
   */
  public function getCustomScalar(string $typeName): ?string {
    return $this->customScalars[$typeName] ?? NULL;
  }

}
