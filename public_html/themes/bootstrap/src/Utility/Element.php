<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Utility\Element.
 */

namespace Drupal\bootstrap\Utility;

use Drupal\bootstrap\Bootstrap;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides helper methods for Drupal render elements.
 *
 * @see \Drupal\Core\Render\Element
 */
class Element extends DrupalAttributes {

  /**
   * The current state of the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The element type.
   *
   * @var string
   */
  protected $type = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $attributePrefix = '#';

  /**
   * Element constructor.
   *
   * @param array|string $element
   *   A render array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function __construct(&$element = [], FormStateInterface $form_state = NULL) {
    if (!is_array($element)) {
      $element = ['#markup' => $element instanceof MarkupInterface ? $element : new FormattableMarkup($element, [])];
    }
    $this->array = &$element;
    $this->formState = $form_state;
  }

  /**
   * Magic get method.
   *
   * This is only for child elements, not properties.
   *
   * @param string $key
   *   The name of the child element to retrieve.
   *
   * @return \Drupal\bootstrap\Utility\Element
   *   The child element object.
   *
   * @throws \InvalidArgumentException
   *   Throws this error when the name is a property (key starting with #).
   */
  public function &__get($key) {
    if (\Drupal\Core\Render\Element::property($key)) {
      throw new \InvalidArgumentException('Cannot dynamically retrieve element property. Please use \Drupal\bootstrap\Utility\Element::getProperty instead.');
    }
    $instance = new self($this->offsetGet($key, []));
    return $instance;
  }

  /**
   * Magic set method.
   *
   * This is only for child elements, not properties.
   *
   * @param string $key
   *   The name of the child element to set.
   * @param mixed $value
   *   The value of $name to set.
   *
   * @throws \InvalidArgumentException
   *   Throws this error when the name is a property (key starting with #).
   */
  public function __set($key, $value) {
    if (\Drupal\Core\Render\Element::property($key)) {
      throw new \InvalidArgumentException('Cannot dynamically retrieve element property. Use \Drupal\bootstrap\Utility\Element::setProperty instead.');
    }
    $this->offsetSet($key, ($value instanceof Element ? $value->getArray() : $value));
  }

  /**
   * Magic isset method.
   *
   * This is only for child elements, not properties.
   *
   * @param string $name
   *   The name of the child element to check.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @throws \InvalidArgumentException
   *   Throws this error when the name is a property (key starting with #).
   */
  public function __isset($name) {
    if (\Drupal\Core\Render\Element::property($name)) {
      throw new \InvalidArgumentException('Cannot dynamically check if an element has a property. Use \Drupal\bootstrap\Utility\Element::unsetProperty instead.');
    }
    return parent::__isset($name);
  }

  /**
   * Magic unset method.
   *
   * This is only for child elements, not properties.
   *
   * @param mixed $name
   *   The name of the child element to unset.
   *
   * @throws \InvalidArgumentException
   *   Throws this error when the name is a property (key starting with #).
   */
  public function __unset($name) {
    if (\Drupal\Core\Render\Element::property($name)) {
      throw new \InvalidArgumentException('Cannot dynamically unset an element property. Use \Drupal\bootstrap\Utility\Element::hasProperty instead.');
    }
    parent::__unset($name);
  }

  /**
   * Identifies the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The array keys of the element's children.
   */
  public function childKeys($sort = FALSE) {
    return \Drupal\Core\Render\Element::children($this->array, $sort);
  }

  /**
   * Retrieves the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return \Drupal\bootstrap\Utility\Element[]
   *   An array child elements.
   */
  public function children($sort = FALSE) {
    $children = [];
    foreach ($this->childKeys($sort) as $child) {
      $children[$child] = new self($this->array[$child]);
    }
    return $children;
  }

  /**
   * Adds a specific Bootstrap class to color a button based on its text value.
   *
   * @return $this
   */
  public function colorize() {
    $button = $this->isButton();

    // @todo refactor this more so it's not just "button" specific.
    $prefix = $button ? 'btn' : 'has';

    // Don't add a class if one is already present in the array.
    $classes = [
      "$prefix-default", "$prefix-primary", "$prefix-success", "$prefix-info",
      "$prefix-warning", "$prefix-danger", "$prefix-link",
    ];

    foreach ($classes as $class) {
      if ($this->hasClass($class)) {
        if ($button && $this->getProperty('split')) {
          $this->addClass($class, $this::SPLIT_BUTTON);
        }
        return $this;
      }
    }

    // Do nothing if setting is disabled.
    if ($button && !Bootstrap::getTheme()->getSetting('button_colorize')) {
      $this->addClass('btn-default');
      return $this;
    }

    if ($value = $this->getProperty('value', $this->getProperty('title'))) {
      $class = "$prefix-" . Bootstrap::cssClassFromString($value, $this->getProperty('button_type', 'default'));
      $this->addClass($class);
      if ($button && $this->getProperty('split')) {
        $this->addClass($class, $this::SPLIT_BUTTON);
      }
    }

    return $this;
  }

  /**
   * Creates a new \Drupal\bootstrap\Utility\Element instance.
   *
   * @param array|string $element
   *   A render array element or a string.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\bootstrap\Utility\Element
   *   The newly created element instance.
   */
  public static function create(&$element = [], FormStateInterface $form_state = NULL) {
    return new self($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function exchangeArray($data) {
    $old = parent::exchangeArray($data);
    return $old;
  }

  /**
   * Retrieves the render array for the element.
   *
   * @return array
   *   The element render array, passed by reference.
   */
  public function &getArray() {
    return $this->array;
  }

  /**
   * Returns the error message filed against the given form element.
   *
   * Form errors higher up in the form structure override deeper errors as well
   * as errors on the element itself.
   *
   * @return string|null
   *   Either the error message for this element or NULL if there are no errors.
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function getError() {
    if (!$this->formState) {
      throw new \BadMethodCallException('The element instance must be constructed with a valid form state object to use this method.');
    }
    return $this->formState->getError($this->array);
  }

  /**
   * Retrieves the render array for the element.
   *
   * @param string $name
   *   The name of the element property to retrieve, not including the # prefix.
   * @param mixed $default
   *   The default to set if property does not exist.
   *
   * @return mixed
   *   The property value, NULL if not set.
   */
  public function &getProperty($name, $default = NULL) {
    return $this->offsetGet("#$name", $default);
  }

  /**
   * Returns the visible children of an element.
   *
   * @return array
   *   The array keys of the element's visible children.
   */
  public function getVisibleChildren() {
    return \Drupal\Core\Render\Element::getVisibleChildren($this->array);
  }

  /**
   * Indicates whether the element has an error set.
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function hasError() {
    $error = $this->getError();
    return isset($error);
  }

  /**
   * Indicates whether the element has a specific property.
   *
   * @param string $name
   *   The property to check.
   */
  public function hasProperty($name) {
    return $this->offsetExists("#$name");
  }

  /**
   * Indicates whether the element is a button.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isButton() {
    return !empty($this->array['#is_button']) || $this->isType(['button', 'submit', 'reset', 'image_button']) || $this->hasClass('btn');
  }

  /**
   * Indicates whether the given element is empty.
   *
   * An element that only has #cache set is considered empty, because it will
   * render to the empty string.
   *
   * @return bool
   *   Whether the given element is empty.
   */
  public function isEmpty() {
    return \Drupal\Core\Render\Element::isEmpty($this->array);
  }

  /**
   * Indicates whether a property on the element is empty.
   *
   * @param string $name
   *   The property to check.
   *
   * @return bool
   *   Whether the given property on the element is empty.
   */
  public function isPropertyEmpty($name) {
    return $this->hasProperty($name) && !empty($this->getProperty($name));
  }

  /**
   * Checks if the element is a specific type of element.
   *
   * @param string|array $type
   *   The element type(s) to check.
   *
   * @return bool
   *   TRUE if element is or one of $type.
   */
  public function isType($type) {
    $property = $this->getProperty('type');
    return $property && in_array($property, (is_array($type) ? $type : [$type]));
  }

  /**
   * Determines if an element is visible.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  public function isVisible() {
    return \Drupal\Core\Render\Element::isVisibleElement($this->array);
  }

  /**
   * Maps an element's properties to its attributes array.
   *
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the HTML attribute names to set on the corresponding
   *   property; e.g., array('#propertyname' => 'attributename'). If both names
   *   are identical except for the leading '#', then an attribute name value is
   *   sufficient and no property name needs to be specified.
   *
   * @return $this
   */
  public function map(array $map) {
    \Drupal\Core\Render\Element::setAttributes($this->array, $map);
    return $this;
  }

  /**
   * Gets properties of a structured array element (keys beginning with '#').
   *
   * @return array
   *   An array of property keys for the element.
   */
  public function properties() {
    return \Drupal\Core\Render\Element::properties($this->array);
  }

  /**
   * Renders the element.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered HTML.
   */
  public function render() {
    /** @var \Drupal\Core\Render\Renderer $renderer */
    static $renderer;
    if (!isset($renderer)) {
      $renderer = \Drupal::service('renderer');
    }
    return $renderer->render($this->array);
  }

  /**
   * Adds Bootstrap button size class to the element.
   *
   * @param string $size
   *   The full button size class to add. If none is provided, it will default
   *   to any set theme setting.
   *
   * @return $this
   */
  public function setButtonSize($size = NULL) {
    // Immediately return if element is not a button.
    if (!$this->isButton()) {
      return $this;
    }

    // Don't add a class if one is already present in the array.
    foreach (['btn-xs', 'btn-sm', 'btn-lg', 'btn-block'] as $class) {
      if ($this->hasClass($class)) {
        // Add the found class to any split buttons.
        if ($this->getProperty('split')) {
          $this->addClass($class, $this::SPLIT_BUTTON);
        }
        return $this;
      }
    }

    // Add any a button size.
    if ($size = $size ?: Bootstrap::getTheme()->getSetting('button_size')) {
      $this->addClass($size);
      if ($this->getProperty('split')) {
        $this->addClass($size, $this::SPLIT_BUTTON);
      }
    }

    return $this;
  }

  /**
   * Flags an element as having an error.
   *
   * @param string $message
   *   (optional) The error message to present to the user.
   *
   * @return $this
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function setError($message = '') {
    if (!$this->formState) {
      throw new \BadMethodCallException('The element instance must be constructed with a valid form state object to use this method.');
    }
    $this->formState->setError($this->array, $message);
    return $this;
  }

  /**
   * Adds an icon to button element based on its text value.
   *
   * @param array $icon
   *   An icon render array.
   *
   * @return $this
   *
   * @see \Drupal\bootstrap\Bootstrap::glyphicon()
   */
  public function setIcon(array $icon = NULL) {
    if ($this->isButton() && !Bootstrap::getTheme()->getSetting('button_iconize')) {
      return $this;
    }
    if ($value = $this->getProperty('value', $this->getProperty('title'))) {
      $icon = isset($icon) ? $icon : Bootstrap::glyphiconFromString($value);
      $this->setProperty('icon', $icon);
    }
    return $this;
  }

  /**
   * Sets the value for a property.
   *
   * @param string $name
   *   The name of the property to set.
   * @param mixed $value
   *   The value of the property to set.
   *
   * @return $this
   */
  public function setProperty($name, $value) {
    $this->array["#$name"] = $value instanceof Element ? $value->getArray() : $value;
    return $this;
  }

  /**
   * Converts an element description into a tooltip based on certain criteria.
   *
   * @param array|\Drupal\bootstrap\Utility\Element|NULL $target_element
   *   The target element render array the tooltip is to be attached to, passed
   *   by reference or an existing Element object. If not set, it will default
   *   this Element instance.
   * @param bool $input_only
   *   Toggle determining whether or not to only convert input elements.
   * @param int $length
   *   The length of characters to determine if description is "simple".
   *
   * @return $this
   */
  public function smartDescription(&$target_element = NULL, $input_only = TRUE, $length = NULL) {
    static $theme;
    if (!isset($theme)) {
      $theme = Bootstrap::getTheme();
    }

    // Determine if tooltips are enabled.
    static $enabled;
    if (!isset($enabled)) {
      $enabled = $theme->getSetting('tooltip_enabled') && $theme->getSetting('forms_smart_descriptions');
    }

    // Immediately return if tooltip descriptions are not enabled.
    if (!$enabled) {
      return $this;
    }

    // Allow a different element to attach the tooltip.
    /** @var Element $target */
    if (is_object($target_element) && $target_element instanceof self) {
      $target = $target_element;
    }
    elseif (isset($target_element) && is_array($target_element)) {
      $target = new self($target_element, $this->formState);
    }
    else {
      $target = $this;
    }

    // Retrieve the length limit for smart descriptions.
    if (!isset($length)) {
      // Disable length checking by setting it to FALSE if empty.
      $length = (int) $theme->getSetting('forms_smart_descriptions_limit') ?: FALSE;
    }

    // Retrieve the allowed tags for smart descriptions. This is primarily used
    // for display purposes only (i.e. non-UI/UX related elements that wouldn't
    // require a user to "click", like a link). Disable length checking by
    // setting it to FALSE if empty.
    static $allowed_tags;
    if (!isset($allowed_tags)) {
      $allowed_tags = array_filter(array_unique(array_map('trim', explode(',', $theme->getSetting('forms_smart_descriptions_allowed_tags') . '')))) ?: FALSE;
    }

    // Return if element or target shouldn't have "simple" tooltip descriptions.
    $html = FALSE;
    if (($input_only && !$target->hasProperty('input'))
      || !$this->getProperty('smart_description', TRUE)
      || !$target->getProperty('smart_description', TRUE)
      || !$this->hasProperty('description')
      || $target->hasAttribute('data-toggle')
      || !Unicode::isSimple($this->getProperty('description'), $length, $allowed_tags, $html)
    ) {
      return $this;
    }

    // Default attributes type.
    $type = DrupalAttributes::ATTRIBUTES;

    // Use #label_attributes for 'checkbox' and 'radio' elements.
    if ($this->isType(['checkbox', 'radio'])) {
      $type = DrupalAttributes::LABEL;
    }
    // Use #wrapper_attributes for 'checkboxes' and 'radios' elements.
    elseif ($this->isType(['checkboxes', 'radios'])) {
      $type = DrupalAttributes::WRAPPER;
    }

    // Retrieve the proper attributes array.
    $attributes = $target->getAttributes($type);

    // Set the tooltip attributes.
    $attributes['title'] = $allowed_tags !== FALSE ? Xss::filter((string) $this->getProperty('description'), $allowed_tags) : $this->getProperty('description');
    $attributes['data-toggle'] = 'tooltip';
    if ($html || $allowed_tags === FALSE) {
      $attributes['data-html'] = 'true';
    }

    // Remove the element description so it isn't (re-)rendered later.
    $this->unsetProperty('description');

    return $this;
  }

  /**
   * Removes a property from the element.
   *
   * @param string $name
   *   The name of the property to unset.
   *
   * @return $this
   */
  public function unsetProperty($name) {
    unset($this->array["#$name"]);
    return $this;
  }

}
