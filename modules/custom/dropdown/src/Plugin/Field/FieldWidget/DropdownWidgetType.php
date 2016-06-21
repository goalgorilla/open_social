<?php

namespace Drupal\dropdown\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'dropdown_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "dropdown_widget_type",
 *   label = @Translation("Dropdown widget"),
 *   field_types = {
 *     "dropdown"
 *   },
 *   multiple_values = FALSE
 * )
 */
class DropdownWidgetType extends WidgetBase {

  /**
   * Abstract over the actual field columns, to allow different field types to
   * reuse those widgets.
   *
   * @var string
   */
  protected $column;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();
    $this->column = $property_names[0];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $this->required = $element['#required'];
    $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $this->has_value = isset($items[$delta]->{$this->column});

    // Add our custom validator.
    $element['#element_validate'][] = array(get_class($this), 'validateElement');
    $element['#key_column'] = $this->column;

    $options = $this->getOptions($items->getEntity());

    $element += array(
      '#type' => 'dropdown',
      '#default_value' => (isset($items[$delta]->value) && isset($options[$items[$delta]->value])) ? $items[$delta]->value : NULL,
      '#options' => $options,
    );

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getSetting('allowed_values');

      $module_handler = \Drupal::moduleHandler();
      $context = array(
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      );
      $module_handler->alter('dropdown_list', $options, $context);

      array_walk_recursive($options, array($this, 'sanitizeLabel'));

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    // Save the value in the element key_column.
    // Note: there is only one item because the field is not multiple value.
    $items = array($element['#key_column'] => $element['#value']);

    $form_state->setValueForElement($element, $items);
  }

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param int $delta
   *   (optional) The delta of the item to get options for. Defaults to 0.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $options = $this->getOptions($items->getEntity());

    $selected_options = array();
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }

  /**
   * Sanitizes a string label to display as an option.
   *
   * @param string $label
   *   The label to sanitize.
   */
  protected function sanitizeLabel(&$label) {
    // Allow a limited set of HTML tags.
    $label = FieldFilteredMarkup::create($label);
  }

}
