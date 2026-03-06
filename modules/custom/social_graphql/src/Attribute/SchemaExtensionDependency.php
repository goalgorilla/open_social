<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Attribute;

/**
 * Declares module, config and theme dependencies for a schema extension.
 *
 * When this attribute is present on a GraphQL schema extension class, the
 * extension is only loaded if all listed dependencies are satisfied (modules
 * enabled, themes exist, config present and enabled). This allows optional
 * API functionality without hard module dependencies in .info.yml.
 *
 * @see \Drupal\social_graphql\SchemaExtensionDependencyFilter
 * @see \Drupal\social_graphql\Hooks\GraphQLSchemaExtensionAlter
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SchemaExtensionDependency {

  /**
   * Constructs the dependency attribute.
   *
   * @param array|null $module
   *   Module names that must be enabled for this extension to load.
   * @param array|null $config
   *   Config names that must exist and be enabled for this extension to load.
   * @param array|null $theme
   *   Theme names that must exist for this extension to load.
   */
  public function __construct(
    public readonly ?array $module = NULL,
    public readonly ?array $config = NULL,
    public readonly ?array $theme = NULL,
  ) {}

  /**
   * Returns the dependency array for filtering.
   *
   * @return array{module?: array<string>, config?: array<string>, theme?: array<string>}
   *   Dependencies keyed by type.
   */
  public function get(): array {
    $dependencies = [];

    if ($this->module !== NULL) {
      $dependencies['module'] = $this->module;
    }

    if ($this->config !== NULL) {
      $dependencies['config'] = $this->config;
    }

    if ($this->theme !== NULL) {
      $dependencies['theme'] = $this->theme;
    }

    return $dependencies;
  }

}
