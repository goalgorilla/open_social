<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'content_block_plugin_field' widget.
 *
 * @FieldWidget(
 *   id = "content_block_plugin_field",
 *   label = @Translation("Content block plugin field"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ContentBlockPluginFieldWidget extends ContentBlockPluginWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    foreach ($this->definitions as $plugin_id => $plugin_definition) {
      $element[$plugin_id] = [
        '#type' => 'select',
        '#title' => $element['value']['#title'] . ' (' . $plugin_definition['type'] . ')',
        '#description' => $element['value']['#description'],
        '#description_display' => 'before',
        '#empty_value' => 'all',
        '#empty_option' => t('All'),
        '#weight' => -1,
        '#states' => [
          'visible' => [
            ':input[name="field_plugin_id[0][value]"]' => [
              'value' => $plugin_id,
            ],
          ],
        ],
      ];

      foreach ($plugin_definition['fields'] as $field) {
        if (isset($form[$field])) {
          $element[$plugin_id]['#options'][$field] = $form[$field]['widget']['#title'];

          $form[$field]['#states'] = [
            'visible' => [
              ':input[name="field_plugin_id[0][value]"]' => [
                'value' => $plugin_id,
              ],
              ':input[name="field_plugin_field[0][' . $plugin_id . ']"]' => [
                ['value' => 'all'],
                ['value' => $field],
              ],
            ],
          ];
        }
        else {
          $element[$plugin_id]['#options'][$field] = $field;
        }
      }
    }

    return $element;
  }

}
