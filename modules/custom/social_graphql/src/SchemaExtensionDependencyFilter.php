<?php

declare(strict_types=1);

namespace Drupal\social_graphql;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\social_graphql\Attribute\SchemaExtensionDependency;

/**
 * Filters GraphQL schema extension definitions by SchemaExtensionDependency.
 *
 * Extensions that have the #[SchemaExtensionDependency] attribute are excluded
 * from the definitions when any of their module, config or theme dependencies
 * are not satisfied.
 */
final class SchemaExtensionDependencyFilter {

  /**
   * Constructs the filter.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ThemeHandlerInterface $themeHandler,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Removes schema extension definitions whose dependencies are not satisfied.
   *
   * @param array<string, array<string, mixed>> $definitions
   *   The schema extension plugin definitions (altered in place).
   */
  public function filterDefinitions(array &$definitions): void {
    foreach (array_keys($definitions) as $plugin_id) {
      $definition = $definitions[$plugin_id];
      $class = $definition['class'] ?? NULL;

      if ($class === NULL || !class_exists($class)) {
        continue;
      }

      $dependencies = $this->getDependenciesFromClass($class);
      if ($dependencies === NULL) {
        continue;
      }

      if (!$this->dependenciesSatisfied($dependencies)) {
        unset($definitions[$plugin_id]);
      }
    }
  }

  /**
   * Reads the SchemaExtensionDependency attribute from a class.
   *
   * @param class-string $class
   *   The schema extension class name.
   *
   * @return array{module?: array<string>, config?: array<string>, theme?: array<string>}|null
   *   The dependency array, or NULL if the class has no attribute.
   */
  private function getDependenciesFromClass(string $class): ?array {
    $reflection = new \ReflectionClass($class);
    $attributes = $reflection->getAttributes(SchemaExtensionDependency::class);

    if ($attributes === []) {
      return NULL;
    }

    return $attributes[0]->newInstance()->get();
  }

  /**
   * Checks whether all dependencies are satisfied.
   *
   * @param array{module?: array<string>, config?: array<string>, theme?: array<string>} $dependencies
   *   The dependency array from SchemaExtensionDependency::get().
   *
   * @return bool
   *   TRUE if all dependencies are satisfied.
   */
  private function dependenciesSatisfied(array $dependencies): bool {
    $module_dependencies = $dependencies['module'] ?? [];
    foreach ($module_dependencies as $module) {
      if (!$this->moduleHandler->moduleExists($module)) {
        return FALSE;
      }
    }

    $theme_dependencies = $dependencies['theme'] ?? [];
    foreach ($theme_dependencies as $theme) {
      if (!$this->themeHandler->themeExists($theme)) {
        return FALSE;
      }
    }

    $config_dependencies = $dependencies['config'] ?? [];
    foreach ($config_dependencies as $config_id) {
      $config = $this->configFactory->get($config_id);
      $status = $config->get('status');
      if ($config->isNew() || (is_bool($status) && !$status)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
