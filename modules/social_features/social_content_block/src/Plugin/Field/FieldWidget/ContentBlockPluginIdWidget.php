<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'content_block_plugin_id' widget.
 *
 * @FieldWidget(
 *   id = "content_block_plugin_id",
 *   label = @Translation("Content block plugin ID"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ContentBlockPluginIdWidget extends ContentBlockPluginWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $value = &$element['value'];
    $value['#type'] = 'select';
    $definitions = $this->contentBlockManager->getDefinitions();

    if (!$element['value']['#default_value']) {
      $element['value']['#default_value'] = key($definitions);
    }

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $value['#options'][$plugin_id] = $this->getLabel($plugin_definition);
    }

    if (count($definitions) === 1) {
      $value['#empty_value'] = key($value['#options']);
      $value['#empty_option'] = reset($value['#options']);
      $value['#disabled'] = TRUE;
    }

    return $element;
  }

}
