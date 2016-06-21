<?php

namespace Drupal\dropdown\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'dropdown' field type.
 *
 * @FieldType(
 *   id = "dropdown",
 *   label = @Translation("Dropdown list"),
 *   description = @Translation("This field stores text values from a dropdown list of allowed 'value => label => description' pairs."),
 *   category = @Translation("Text"),
 *   default_widget = "dropdown_widget_type",
 *   default_formatter = "dropdown_field_formatter",
 * )
 */
class Dropdown extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'allowed_values' => array(),
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->addConstraint('Length', array('max' => 255))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $allowed_values = $this->getSetting('allowed_values');

    $element['allowed_values'] = array(
      '#type' => 'textarea',
      '#title' => t('Allowed values list'),
      '#default_value' => $this->allowedValuesString($allowed_values),
      '#rows' => 10,
      '#access' => TRUE,
      '#element_validate' => array(array(get_class($this), 'validateAllowedValues')),
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#allowed_values' => $allowed_values,
    );

    $element['allowed_values']['#description'] = $this->allowedValuesDescription();

    return $element;
  }

  /**
   * #element_validate callback for options field allowed values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value'], $element['#field_has_data']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      // Prevent removing values currently in use.
      if ($element['#field_has_data']) {
        $lost_keys = array_keys(array_diff_key($element['#allowed_values'], $values));
        if (_options_values_in_use($element['#entity_type'], $element['#field_name'], $lost_keys)) {
          $form_state->setError($element, t('Allowed values list: some values are being removed while currently in use.'));
        }
      }

      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->values['value']) && (string) $this->values['value'] !== '0';
  }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  protected function allowedValuesString($values) {
    $lines = array();
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $lines[$key] = implode("|", $value);;
      }
    }
    return implode("\n", $lines);
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   * @param bool $has_data
   *   The current field already has data inserted or not.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListTextItem::allowedValuesString()
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = array();

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = array();
      // @TODO Explicit key is necessary !
      if (preg_match('/(.*)\|(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $value = trim($matches[1]);
        $label = trim($matches[2]);
        $description = trim($matches[3]);

        $values[$value] = array(
          'value' => $value,
          'label' => $label,
          'description' => $description,
        );
      }
      elseif (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $value = trim($matches[1]);
        $label = trim($matches[2]);
        $values[$value] = array(
          'value' => $value,
          'label' => $label,
        );
      }
    }

    return $values;
  }

  /**
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   *
   * @return string
   *   The error message if the specified value is invalid, NULL otherwise.
   */
  protected static function validateAllowedValue($option) {}

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format value|label|description.');
    $description .= '<br/>' . t('The value is the stored value. The label and description will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The description is optional: if a line contains value|label a description will not be shown.');
    $description .= '</p>';
    return $description;
  }

}
