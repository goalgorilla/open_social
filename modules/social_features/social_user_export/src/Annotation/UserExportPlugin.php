<?php

namespace Drupal\social_user_export\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a User export plugin item annotation object.
 *
 * @see \Drupal\social_user_export\Plugin\UserExportPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class UserExportPlugin extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The plugin weight.
   */
  public int $weight;

  /**
   * The things that this plugin depends on.
   *
   * Follows the same structure as config dependencies allowing `module`,
   * `config` and `theme` as top-level keys. All dependencies are considered
   * enforced.
   *
   * @var array|null
   */
  public ?array $dependencies;

  /**
   * {@inheritdoc}
   */
  protected function parse(array $values) {
    $definition = parent::parse($values);

    // Validate the $dependencies array to help developers avoid mistakes.
    if (isset($definition['dependencies'])) {
      if (!is_array($definition['dependencies'])) {
        throw new \InvalidArgumentException("dependencies must by an array for UserExportPlugin annotation (id: {$definition['id']})");
      }

      $allowed_keys = ['module', 'config', 'theme'];
      foreach ($definition['dependencies'] as $key => $value) {
        if (!in_array($key, $allowed_keys, TRUE)) {
          throw new \InvalidArgumentException("invalid key '$key' in dependencies of UserExportPlugin (id: {$definition['id']}), must be one of " . implode(", ", $allowed_keys));
        }

        if (!is_array($value)) {
          throw new \InvalidArgumentException("Value for '$key' in dependencies of UserExportPlugin (id: {$definition['id']}) must be an array.");
        }
      }
    }

    return $definition;
  }

}
