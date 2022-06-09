<?php

namespace Drupal\social_group;

use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Defines the join manager interface.
 */
interface JoinManagerInterface extends ContextAwarePluginManagerInterface {

  /**
   * Returns list of entity types and their bundles that support join methods.
   */
  public function relations(): array;

  /**
   * Preprocess theme variables for templates.
   *
   * @param array $variables
   *   The variables array (modify in place).
   * @param string $hook
   *   The name of the theme hook.
   */
  public function preprocess(array &$variables, string $hook): void;

  /**
   * Check if specific bundle supports selected the join method.
   *
   * @param string $bundle
   *   The bundle.
   * @param string $method
   *   The join method.
   */
  public function hasMethod(string $bundle, string $method): bool;

}
