<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\PreprocessBase.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;

/**
 * Base preprocess class used to build the necessary variables for templates.
 *
 * @ingroup theme_preprocess
 */
class PreprocessBase extends PluginBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    $vars = Variables::create($variables);
    if ($vars->element) {
      $this->preprocessElement($vars, $hook, $info);
    }
    $this->preprocessVariables($vars, $hook, $info);
  }

  /**
   * Ensures all attributes have been converted to an Attribute object.
   *
   * @param \Drupal\bootstrap\Utility\Variables $variables
   *   A variables object.
   * @param string $hook
   *   The name of the theme hook.
   * @param array $info
   *   The theme hook info array.
   */
  protected function preprocessAttributes(Variables $variables, $hook, array $info) {
    foreach ($variables as $name => $value) {
      if (strpos($name, 'attributes') !== FALSE && is_array($value)) {
        $variables[$name] = new Attribute($value);
      }
    }
  }

  /**
   * Converts any set description variable into a traversable array.
   *
   * @param \Drupal\bootstrap\Utility\Variables $variables
   *   A variables object.
   * @param string $hook
   *   The name of the theme hook.
   * @param array $info
   *   The theme hook info array.
   *
   * @see https://www.drupal.org/node/2324025
   */
  protected function preprocessDescription(Variables $variables, $hook, array $info) {
    if ($variables->offsetGet('description')) {
      // Retrieve the description attributes.
      $description_attributes = $variables->offsetGet('description_attributes', []);

      // Remove standalone description attributes.
      $variables->offsetUnset('description_attributes');

      // Build the description attributes.
      if ($id = $variables->getAttribute('id')) {
        $variables->setAttribute('aria-describedby', "$id--description");
        $description_attributes['id'] = "$id--description";
      }

      // Replace the description variable.
      $variables->offsetSet('description', [
        'attributes' => new Attribute($description_attributes),
        'content' => $variables['description'],
        'position' => $variables->offsetGet('description_display', 'after'),
      ]);
    }
  }

  /**
   * Preprocess the variables array if an element is present.
   *
   * @param \Drupal\bootstrap\Utility\Variables $variables
   *   A variables object.
   * @param string $hook
   *   The name of the theme hook.
   * @param array $info
   *   The theme hook info array.
   */
  protected function preprocessElement(Variables $variables, $hook, array $info) {}

  /**
   * Preprocess the variables array.
   *
   * @param \Drupal\bootstrap\Utility\Variables $variables
   *   A variables object.
   * @param string $hook
   *   The name of the theme hook.
   * @param array $info
   *   The theme hook info array.
   */
  protected function preprocessVariables(Variables $variables, $hook, array $info) {}

}
