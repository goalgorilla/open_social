<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\ProcessManager.
 */

namespace Drupal\bootstrap\Plugin;

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Theme;
use Drupal\bootstrap\Utility\Element;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Manages discovery and instantiation of Bootstrap form process callbacks.
 */
class ProcessManager extends PluginManager {

  /**
   * Constructs a new \Drupal\bootstrap\Plugin\ProcessManager object.
   *
   * @param \Drupal\bootstrap\Theme $theme
   *   The theme to use for discovery.
   */
  public function __construct(Theme $theme) {
    parent::__construct($theme, 'Plugin/Process', 'Drupal\bootstrap\Plugin\Process\ProcessInterface', 'Drupal\bootstrap\Annotation\BootstrapProcess');
    $this->setCacheBackend(\Drupal::cache('discovery'), 'theme:' . $theme->getName() . ':process', $this->getCacheTags());
  }

  /**
   * Global #process callback for form elements.
   *
   * @param array $element
   *   The element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The altered element array.
   *
   * @see \Drupal\bootstrap\Plugin\Alter\ElementInfo::alter
   */
  public static function process(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#bootstrap_ignore_process'])) {
      return $element;
    }

    static $theme;
    if (!isset($theme)) {
      $theme = Bootstrap::getTheme();
    }

    $e = Element::create($element, $form_state);

    // Add "form-inline" class.
    if ($e->hasClass('container-inline')) {
      $e->replaceClass('container-inline', 'form-inline');
    }
    if ($e->isType(['color', 'date', 'number', 'password', 'password_confirm', 'range', 'tel', 'weight'])) {
      $e->addClass('form-inline', 'wrapper_attributes');
    }

    // Check for errors and set the "has_error" property flag.
    $errors = $e->getError();
    $e->setProperty('errors', $errors);
    if (isset($errors) || ($e->getProperty('required') && $theme->getSetting('forms_required_has_error'))) {
      $e->setProperty('has_error', TRUE);
    }

    // Automatically inject the nearest button found after this element if
    // #input_group_button exists.
    if ($e->getProperty('input_group_button')) {
      // Obtain the parent array to limit search.
      $array_parents = $e->getProperty('array_parents', []);

      // Remove the current element from the array.
      array_pop($array_parents);

      // Retrieve the parent element.
      $parent = Element::create(NestedArray::getValue($complete_form, $array_parents), $form_state);

      // Find the closest button.
      if ($button = self::findButton($parent)) {
        $e->setProperty('field_suffix', $button->setIcon()->getArray());
        $button->setProperty('access', FALSE);
      }
    }

    return $element;
  }

  /**
   * Traverses an element to find the closest button.
   *
   * @param \Drupal\bootstrap\Utility\Element $element
   *   The element to iterate over.
   *
   * @return \Drupal\bootstrap\Utility\Element|FALSE
   *   The first button element or FALSE if no button could be found.
   */
  protected static function &findButton(Element $element) {
    $button = FALSE;
    foreach ($element->children() as $child) {
      if ($child->isButton()) {
        $button = $child;
      }
      if ($result = &self::findButton($child)) {
        $button = $result;
      }
    }
    return $button;
  }

}
