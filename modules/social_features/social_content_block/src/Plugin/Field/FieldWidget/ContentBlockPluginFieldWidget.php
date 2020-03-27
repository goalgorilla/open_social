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
    $selected_plugin_id = $items->getEntity()->field_plugin_id->value;
    $selector = $this->contentBlockManager->getSelector('field_plugin_id', 'value', $element['#field_parents']);

    foreach ($this->contentBlockManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $element[$plugin_id] = [
        '#type' => 'select',
        '#title' => $element['value']['#title'],
        '#description' => $element['value']['#description'],
        '#empty_value' => 'all',
        '#empty_option' => t('All'),
        '#weight' => -1,
        '#states' => [
          'visible' => [
            $selector => [
              'value' => $plugin_id,
            ],
          ],
        ],
      ];

      if ($selected_plugin_id === $plugin_id) {
        $element[$plugin_id]['#default_value'] = $element['value']['#default_value'];
      }

      foreach ($plugin_definition['fields'] as $field) {
        if (isset($form[$field])) {
          $element[$plugin_id]['#options'][$field] = $form[$field]['widget']['target_id']['#title'];

          $form[$field]['#states'] = [
            'visible' => [
              $selector => [
                'value' => $plugin_id,
              ],
              $this->contentBlockManager->getSelector('field_plugin_field', $plugin_id) => [
                ['value' => 'all'],
                ['value' => $field],
              ],
            ],
          ];
        }
        else {
          // Add the field machine name instead of the field label when the
          // field still not added to the form structure. The field will be
          // processed in the following place:
          // @see \Drupal\social_content_block\ContentBuilder::processBlockForm()
          $element[$plugin_id]['#options'][$field] = $field;
        }
      }
    }

    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    return $element;
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
    $value = $form_state->getValue([
      'field_plugin_field',
      0,
      $form_state->getValue(['field_plugin_id', 0, 'value']),
    ]);

    if ($value === 'all') {
      $form_state->setValueForElement($element, NULL);
    }
    else {
      $form_state->setValueForElement($element, ['value' => $value]);
    }
  }

}
