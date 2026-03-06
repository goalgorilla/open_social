<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Hooks;

use Drupal\hux\Attribute\Alter;
use Drupal\social_graphql\SchemaExtensionDependencyFilter;

/**
 * Hux alter for GraphQL schema extension definitions.
 *
 * Removes schema extension definitions that declare
 * #[SchemaExtensionDependency] when any of their module, config or theme
 * dependencies are not satisfied.
 */
final class GraphQLSchemaExtensionAlter {

  /**
   * Constructs the alter.
   *
   * @param \Drupal\social_graphql\SchemaExtensionDependencyFilter $filter
   *   The schema extension dependency filter.
   */
  public function __construct(
    private readonly SchemaExtensionDependencyFilter $filter,
  ) {}

  /**
   * Implements hook_graphql_schema_extension_alter().
   *
   * @param array<string, array<string, mixed>> $definitions
   *   The schema extension plugin definitions (altered in place).
   */
  #[Alter('graphql_schema_extension')]
  public function filterByDependency(array &$definitions): void {
    $this->filter->filterDefinitions($definitions);
  }

}
