<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Utility\Attributes.
 */

namespace Drupal\bootstrap\Utility;

/**
 * Class to help modify attributes.
 */
class Attributes extends ArrayObject {

  /**
   * {@inheritdoc}
   */
  public function __construct(array &$array = []) {
    $this->array = &$array;
  }

  /**
   * Add class(es) to the array.
   *
   * @param string|array $class
   *   An individual class or an array of classes to add.
   *
   * @see \Drupal\bootstrap\Utility\Attributes::getClasses()
   */
  public function addClass($class) {
    $classes = &$this->getClasses();
    $classes = array_unique(array_merge($classes, (array) $class));
  }

  /**
   * Retrieve a specific attribute from the array.
   *
   * @param string $name
   *   The specific attribute to retrieve.
   * @param mixed $default
   *   (optional) The default value to set if the attribute does not exist.
   *
   * @return mixed
   *   A specific attribute value, passed by reference.
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::offsetGet()
   */
  public function &getAttribute($name, $default = NULL) {
    return $this->offsetGet($name, $default);
  }

  /**
   * Retrieves classes from the array.
   *
   * @return array
   *   The classes array, passed by reference.
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::offsetGet()
   */
  public function &getClasses() {
    $classes = &$this->offsetGet('class', []);
    $classes = array_unique($classes);
    return $classes;
  }

  /**
   * Indicates whether a specific attribute is set.
   *
   * @param string $name
   *   The attribute to search for.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::offsetExists()
   */
  public function hasAttribute($name) {
    return $this->offsetExists($name);
  }

  /**
   * Indicates whether a class is present in the array.
   *
   * @param string $class
   *   The class to search for.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\bootstrap\Utility\Attributes::getClasses()
   */
  public function hasClass($class) {
    return array_search($class, $this->getClasses()) !== FALSE;
  }

  /**
   * Removes an attribute from the array.
   *
   * @param string|array $name
   *   The name of the attribute to remove.
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::offsetUnset()
   */
  public function removeAttribute($name) {
    $this->offsetUnset($name);
  }

  /**
   * Removes a class from the attributes array.
   *
   * @param string|array $class
   *   An individual class or an array of classes to remove.
   *
   * @see \Drupal\bootstrap\Utility\Attributes::getClasses()
   */
  public function removeClass($class) {
    $classes = &$this->getClasses();
    $classes = array_values(array_diff($classes, (array) $class));
  }

  /**
   * Replaces a class in the attributes array.
   *
   * @param string $old
   *   The old class to remove.
   * @param string $new
   *   The new class. It will not be added if the $old class does not exist.
   *
   * @see \Drupal\bootstrap\Utility\Attributes::getClasses()
   */
  public function replaceClass($old, $new) {
    $classes = &$this->getClasses();
    $key = array_search($old, $classes);
    if ($key !== FALSE) {
      $classes[$key] = $new;
    }
  }

  /**
   * Sets an attribute on the array.
   *
   * @param string $name
   *   The name of the attribute to set.
   * @param mixed $value
   *   The value of the attribute to set.
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::offsetSet()
   */
  public function setAttribute($name, $value) {
    $this->offsetSet($name, $value);
  }

  /**
   * Sets multiple attributes on the array.
   *
   * @param array $values
   *   An associative key/value array of attributes to set.
   *
   * @see \Drupal\bootstrap\Utility\ArrayObject::merge()
   */
  public function setAttributes(array $values) {
    $this->merge($values);
  }

}
