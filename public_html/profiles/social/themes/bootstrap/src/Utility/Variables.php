<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Utility\Variables.
 */

namespace Drupal\bootstrap\Utility;

/**
 * Class to help modify template variables.
 */
class Variables extends DrupalAttributes {

  /**
   * An element object.
   *
   * @var \Drupal\bootstrap\Utility\Element
   */
  public $element;

  /**
   * Element constructor.
   *
   * @param array $variables
   *   A theme hook variables array.
   */
  public function __construct(array &$variables) {
    $this->array = &$variables;
    $this->element = isset($variables['element']) ? Element::create($variables['element']) : FALSE;
  }

  /**
   * Creates a new \Drupal\bootstrap\Utility\Variables instance.
   *
   * @param array $variables
   *   A theme hook variables array.
   *
   * @return \Drupal\bootstrap\Utility\Variables
   *   The newly created variables instance.
   */
  public static function create(array &$variables) {
    return new self($variables);
  }

  /**
   * Maps an element's properties to the variables attributes array.
   *
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the variable names to set in the variables array; e.g.,
   *   array('#property_name' => 'variable_name'). If both names are identical
   *   except for the leading '#', then an attribute name value is sufficient
   *   and no property name needs to be specified.
   * @param bool $overwrite
   *   If the variable exists, it will be overwritten. This does not apply to
   *   attribute arrays, they will always be merged recursively.
   *
   * @return $this
   */
  public function map(array $map, $overwrite = TRUE) {
    // Immediately return if there is no element in the variable array.
    if (!$this->element) {
      return $this;
    }

    // Iterate over each map item.
    foreach ($map as $property => $variable) {
      // If the key is numeric, the attribute name needs to be taken over.
      if (is_int($property)) {
        $property = $variable;
      }

      // Merge attributes from the element.
      if (strpos($property, 'attributes') !== FALSE) {
        $this->setAttributes($this->element->getAttributes($property)->getArrayCopy(), $variable);
      }
      // Set normal variable.
      elseif ($overwrite || !$this->offsetExists($variable)) {
        $this->offsetSet($variable, $this->element->getProperty($property));
      }
    }
    return $this;
  }

}
