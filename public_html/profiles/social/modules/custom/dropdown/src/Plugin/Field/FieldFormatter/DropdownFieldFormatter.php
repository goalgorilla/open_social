<?php

/**
 * @file
 * Contains \Drupal\dropdown\Plugin\Field\FieldFormatter\DropdownFieldFormatter.
 */

namespace Drupal\dropdown\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'dropdown_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "dropdown_field_formatter",
 *   label = @Translation("Dropdown field formatter"),
 *   field_types = {
 *     "dropdown"
 *   }
 * )
 */
class DropdownFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {

    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return $this->getLabelForValue($item->value);
  }

  /**
   * Display dropdown labels.
   */
  public function getLabelForValue($value) {
    $settings = $this->getFieldSettings();
    $allowed_values = $settings['allowed_values'];

    foreach ($allowed_values as $allowed_value) {
      if (isset($allowed_value['value']) && $allowed_value['value'] === $value) {
        $label = $allowed_value['label'];
        return Html::escape($label);
      }
    }
  }

}
